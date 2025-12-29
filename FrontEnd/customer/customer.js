import * as THREE from 'three';

const canvas = document.getElementById('threeCanvas');
const fadeEl = document.getElementById('fade');
const sceneTitleEl = document.getElementById('sceneTitle');
const sceneHintEl = document.getElementById('sceneHint');
const btnBack = document.getElementById('btnBack');
const overlayEl = document.getElementById('overlay');
const btnEnter = document.getElementById('btnEnter');
const searchContainer = document.getElementById('searchContainer');
const searchBar = document.getElementById('searchBar');
const floorSelector = document.getElementById('floorSelector');

const renderer = new THREE.WebGLRenderer({ canvas, antialias: true });
renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
renderer.outputColorSpace = THREE.SRGBColorSpace;
renderer.toneMapping = THREE.ACESFilmicToneMapping;
renderer.toneMappingExposure = 1.12;
renderer.shadowMap.enabled = true;
renderer.shadowMap.type = THREE.PCFSoftShadowMap;

window.addEventListener('mousemove', (event) => {
  mouse.x = (event.clientX / window.innerWidth) * 2 - 1;
  mouse.y = - (event.clientY / window.innerHeight) * 2 + 1;
});


const scene = new THREE.Scene();
scene.background = new THREE.Color('#05070c');

const camera = new THREE.PerspectiveCamera(55, 1, 0.1, 250);
camera.position.set(0, 1.6, 9);

const raycaster = new THREE.Raycaster();
const pointerNdc = new THREE.Vector2();

const clock = new THREE.Clock();

const textureLoader = new THREE.TextureLoader();

let hallwayParticles = null;
let hallwayParticlesMeta = null;

const ambient = new THREE.AmbientLight(0xffffff, 0.55);
scene.add(ambient);

const keyLight = new THREE.DirectionalLight(0xffffff, 0.85);
keyLight.position.set(4, 8, 6);
keyLight.castShadow = true;
keyLight.shadow.mapSize.set(2048, 2048);
keyLight.shadow.camera.near = 0.5;
keyLight.shadow.camera.far = 140;
keyLight.shadow.camera.left = -30;
keyLight.shadow.camera.right = 30;
keyLight.shadow.camera.top = 30;
keyLight.shadow.camera.bottom = -30;
keyLight.shadow.bias = -0.00012;
scene.add(keyLight);

const fillLight = new THREE.DirectionalLight(0x9bb7ff, 0.45);
fillLight.position.set(-6, 5, 2);
scene.add(fillLight);

const floorMat = new THREE.MeshStandardMaterial({ color: 0x0c1020, metalness: 0.1, roughness: 0.9 });
const wallMat = new THREE.MeshStandardMaterial({ color: 0x111a33, metalness: 0.0, roughness: 0.95 });
const neonMat = new THREE.MeshStandardMaterial({ color: 0x2d6cff, emissive: 0x2d6cff, emissiveIntensity: 1.2, metalness: 0.0, roughness: 0.3 });
const doorMat = new THREE.MeshStandardMaterial({ color: 0x1b2747, emissive: 0x0c1430, emissiveIntensity: 0.8, metalness: 0.2, roughness: 0.6 });
const shopFrontMat = new THREE.MeshStandardMaterial({ color: 0x161d35, metalness: 0.05, roughness: 0.9 });

const hallwayFloorMat = new THREE.MeshStandardMaterial({ color: 0xf2f2f0, metalness: 0.0, roughness: 0.98 });
const hallwayWallMat = new THREE.MeshStandardMaterial({ color: 0xf7f7f5, metalness: 0.0, roughness: 0.96 });
const hallwayAccentMat = new THREE.MeshStandardMaterial({ color: 0xffffff, emissive: 0xffffff, emissiveIntensity: 0.08, metalness: 0.0, roughness: 0.9 });
const glassMat = new THREE.MeshPhysicalMaterial({
  color: 0xffffff,
  metalness: 0.0,
  roughness: 0.08,
  transmission: 0.95,
  thickness: 0.6,
  transparent: true,
  opacity: 0.95
});
glassMat.depthWrite = false;
glassMat.side = THREE.DoubleSide;
glassMat.polygonOffset = true;
glassMat.polygonOffsetFactor = -2;
glassMat.polygonOffsetUnits = -2;

const storefrontFrameMat = new THREE.MeshStandardMaterial({ color: 0x1f2937, metalness: 0.25, roughness: 0.55 });
const storefrontBaseMat = new THREE.MeshStandardMaterial({ color: 0x111827, metalness: 0.22, roughness: 0.65 });
const ceilingLightMat = new THREE.MeshStandardMaterial({ color: 0xffffff, emissive: 0xffffff, emissiveIntensity: 0.22, metalness: 0.0, roughness: 0.95 });

const clickable = [];

const state = {
  mode: 'outside',
  transitioning: false,
  hallwayProgress: 0,
  suppliers: [],
  floorIndex: 0,
  shopsPerFloor: 10,
  hallwayMaxProgress: 150,
  cameraTargetPos: new THREE.Vector3(0, 1.6, 9),
  cameraTargetLookAt: new THREE.Vector3(0, 1.5, 0),
  cameraLookAt: new THREE.Vector3(0, 1.5, 0)
};

function getSuppliersForFloor() {
  const suppliers = state.suppliers.length
    ? state.suppliers
    : Array.from({ length: 10 }).map((_, idx) => ({ supplier_id: idx + 1, company_name: `Shop ${idx + 1}` }));

  const start = state.floorIndex * state.shopsPerFloor;
  const end = start + state.shopsPerFloor;
  return {
    slice: suppliers.slice(start, end),
    total: suppliers.length
  };
}

function makeTextTexture(text, opts = {}) {
  const {
    width = 768,
    height = 256,
    bg = 'rgba(255,255,255,0.0)',
    color = '#0b1020',
    font = '600 64px Inter, Arial',
    subText,
    subFont = '400 28px Inter, Arial'
  } = opts;

  const cnv = document.createElement('canvas');
  cnv.width = width;
  cnv.height = height;
  const ctx = cnv.getContext('2d');
  ctx.fillStyle = bg;
  ctx.fillRect(0, 0, width, height);

  ctx.fillStyle = color;
  ctx.textAlign = 'center';
  ctx.textBaseline = 'middle';
  ctx.font = font;
  ctx.fillText(text, width / 2, height / 2 - (subText ? 18 : 0), width - 40);

  if (subText) {
    ctx.globalAlpha = 0.75;
    ctx.font = subFont;
    ctx.fillText(subText, width / 2, height / 2 + 44, width - 40);
    ctx.globalAlpha = 1;
  }

  const tex = new THREE.CanvasTexture(cnv);
  tex.colorSpace = THREE.SRGBColorSpace;
  tex.wrapS = THREE.RepeatWrapping;
  tex.wrapT = THREE.RepeatWrapping;
  tex.repeat.set(1, 1);
  tex.needsUpdate = true;
  return tex;
}

function makeWoodTexture() {
  const w = 512;
  const h = 1024;
  const cnv = document.createElement('canvas');
  cnv.width = w;
  cnv.height = h;
  const ctx = cnv.getContext('2d');

  // Base wood color - rich brown
  ctx.fillStyle = '#6b4423';
  ctx.fillRect(0, 0, w, h);

  // Wood grain pattern - vertical grain lines
  for (let x = 0; x < w; x++) {
    const t = x / w;
    const wave = Math.sin(t * Math.PI * 8) * 0.8 + Math.sin(t * Math.PI * 18) * 0.4 + Math.sin(t * Math.PI * 35) * 0.2;
    const v = 50 + Math.floor(wave * 35);
    const r = 107 + v;
    const g = 70 + Math.floor(v * 0.65);
    const b = 35 + Math.floor(v * 0.3);
    ctx.fillStyle = `rgba(${r},${g},${b},0.35)`;
    ctx.fillRect(x, 0, 1, h);
  }

  // Add wood knots and variations
  for (let i = 0; i < 12000; i++) {
    const x = Math.random() * w;
    const y = Math.random() * h;
    const a = Math.random() * 0.08;
    const darkness = Math.random() * 0.3;
    ctx.fillStyle = `rgba(${Math.floor(30 * darkness)},${Math.floor(20 * darkness)},${Math.floor(10 * darkness)},${a})`;
    ctx.fillRect(x, y, 1 + Math.random(), 1 + Math.random());
  }

  // Add lighter wood highlights
  for (let i = 0; i < 6000; i++) {
    const x = Math.random() * w;
    const y = Math.random() * h;
    const a = Math.random() * 0.06;
    ctx.fillStyle = `rgba(255,230,200,${a})`;
    ctx.fillRect(x, y, 1, 1);
  }

  // Add horizontal grain lines for depth
  for (let y = 0; y < h; y += 15 + Math.random() * 10) {
    ctx.globalAlpha = 0.15;
    ctx.strokeStyle = `rgba(80,50,25,${0.3 + Math.random() * 0.2})`;
    ctx.lineWidth = 1;
    ctx.beginPath();
    ctx.moveTo(0, y);
    ctx.lineTo(w, y);
    ctx.stroke();
  }
  ctx.globalAlpha = 1;

  // Add door panel outlines for a more realistic door
  ctx.globalAlpha = 0.25;
  ctx.strokeStyle = 'rgba(40,25,15,0.4)';
  ctx.lineWidth = 6;
  ctx.strokeRect(30, 30, w - 60, h - 60);
  ctx.globalAlpha = 0.15;
  ctx.lineWidth = 4;
  ctx.strokeRect(70, 120, w - 140, h - 240);
  ctx.globalAlpha = 1;

  const tex = new THREE.CanvasTexture(cnv);
  tex.colorSpace = THREE.SRGBColorSpace;
  tex.wrapS = THREE.RepeatWrapping;
  tex.wrapT = THREE.RepeatWrapping;
  tex.needsUpdate = true;
  return tex;
}

function makeWoodBumpTexture() {
  const w = 512;
  const h = 1024;
  const cnv = document.createElement('canvas');
  cnv.width = w;
  cnv.height = h;
  const ctx = cnv.getContext('2d');
  ctx.fillStyle = '#808080';
  ctx.fillRect(0, 0, w, h);

  // Create bump map with wood grain patterns
  for (let x = 0; x < w; x++) {
    const t = x / w;
    const wave = Math.sin(t * Math.PI * 8) * 0.9 + Math.sin(t * Math.PI * 18) * 0.45 + Math.sin(t * Math.PI * 35) * 0.2;
    const g = 128 + Math.floor(wave * 42);
    ctx.fillStyle = `rgba(${g},${g},${g},0.28)`;
    ctx.fillRect(x, 0, 1, h);
  }

  // Add grain texture variations
  for (let i = 0; i < 15000; i++) {
    const x = Math.random() * w;
    const y = Math.random() * h;
    const a = Math.random() * 0.12;
    const gray = 100 + Math.random() * 50;
    ctx.fillStyle = `rgba(${gray},${gray},${gray},${a})`;
    ctx.fillRect(x, y, 1 + Math.random(), 1 + Math.random());
  }

  // Add horizontal grain lines for depth in bump
  for (let y = 0; y < h; y += 15 + Math.random() * 10) {
    const gray = 140 + Math.random() * 30;
    ctx.globalAlpha = 0.2;
    ctx.strokeStyle = `rgba(${gray},${gray},${gray},0.4)`;
    ctx.lineWidth = 1;
    ctx.beginPath();
    ctx.moveTo(0, y);
    ctx.lineTo(w, y);
    ctx.stroke();
  }
  ctx.globalAlpha = 1;

  const tex = new THREE.CanvasTexture(cnv);
  tex.colorSpace = THREE.NoColorSpace;
  tex.wrapS = THREE.RepeatWrapping;
  tex.wrapT = THREE.RepeatWrapping;
  tex.needsUpdate = true;
  return tex;
}

function makeDoorTexture() {
  const w = 512;
  const h = 1024;
  const cnv = document.createElement('canvas');
  cnv.width = w;
  cnv.height = h;
  const ctx = cnv.getContext('2d');

  ctx.fillStyle = '#0a0f1a';
  ctx.fillRect(0, 0, w, h);

  const g = ctx.createLinearGradient(0, 0, w, 0);
  g.addColorStop(0, 'rgba(255,255,255,0.06)');
  g.addColorStop(0.35, 'rgba(255,255,255,0.02)');
  g.addColorStop(0.65, 'rgba(255,255,255,0.0)');
  g.addColorStop(1, 'rgba(255,255,255,0.04)');
  ctx.fillStyle = g;
  ctx.fillRect(0, 0, w, h);

  ctx.globalAlpha = 0.55;
  ctx.strokeStyle = 'rgba(255,255,255,0.18)';
  ctx.lineWidth = 6;
  ctx.strokeRect(28, 28, w - 56, h - 56);
  ctx.globalAlpha = 0.32;
  ctx.lineWidth = 4;
  ctx.strokeRect(60, 96, w - 120, h - 180);
  ctx.strokeRect(60, 320, w - 120, h - 480);
  ctx.strokeRect(60, 650, w - 120, h - 770);

  ctx.globalAlpha = 0.9;
  ctx.fillStyle = 'rgba(229,231,235,0.65)';
  ctx.fillRect(w * 0.73, h * 0.56, 12, h * 0.18);
  ctx.fillRect(w * 0.71, h * 0.64, 36, 10);
  ctx.globalAlpha = 1;

  for (let i = 0; i < 7000; i++) {
    const x = Math.random() * w;
    const y = Math.random() * h;
    const a = Math.random() * 0.08;
    ctx.fillStyle = `rgba(255,255,255,${a})`;
    ctx.fillRect(x, y, 1, 1);
  }

  const tex = new THREE.CanvasTexture(cnv);
  tex.colorSpace = THREE.SRGBColorSpace;
  tex.wrapS = THREE.ClampToEdgeWrapping;
  tex.wrapT = THREE.ClampToEdgeWrapping;
  tex.needsUpdate = true;
  return tex;
}

function makeRippleNormalTexture() {
  const size = 512;
  const cnv = document.createElement('canvas');
  cnv.width = size;
  cnv.height = size;
  const ctx = cnv.getContext('2d');

  const img = ctx.createImageData(size, size);
  const cx = size * 0.5;
  const cy = size * 0.5;

  for (let y = 0; y < size; y++) {
    for (let x = 0; x < size; x++) {
      const dx = (x - cx) / size;
      const dy = (y - cy) / size;
      const r = Math.sqrt(dx * dx + dy * dy);

      const a = Math.sin((r * 40.0 + dx * 10.0 - dy * 8.0) * Math.PI * 2);
      const b = Math.sin((r * 26.0 - dx * 14.0 + dy * 12.0) * Math.PI * 2);
      const h = 0.5 + 0.5 * (0.62 * a + 0.38 * b);

      const nx = (h - 0.5) * 0.8 + (Math.random() - 0.5) * 0.04;
      const ny = (0.5 - h) * 0.8 + (Math.random() - 0.5) * 0.04;
      const nz = 1.0;

      const len = Math.sqrt(nx * nx + ny * ny + nz * nz);
      const r8 = Math.floor(((nx / len) * 0.5 + 0.5) * 255);
      const g8 = Math.floor(((ny / len) * 0.5 + 0.5) * 255);
      const b8 = Math.floor(((nz / len) * 0.5 + 0.5) * 255);

      const i = (y * size + x) * 4;
      img.data[i + 0] = r8;
      img.data[i + 1] = g8;
      img.data[i + 2] = b8;
      img.data[i + 3] = 255;
    }
  }

  ctx.putImageData(img, 0, 0);
  const tex = new THREE.CanvasTexture(cnv);
  tex.colorSpace = THREE.NoColorSpace;
  tex.wrapS = THREE.RepeatWrapping;
  tex.wrapT = THREE.RepeatWrapping;
  tex.repeat.set(3, 3);
  tex.needsUpdate = true;
  return tex;
}

function getShopCategoryLabel(supplier) {
  const d = supplier && typeof supplier.description === 'string' ? supplier.description.trim() : '';
  if (d) return d;
  const labels = ['Shoes', 'Clothing', 'Accessories', 'Beauty', 'Electronics', 'Lifestyle'];
  const id = Number(supplier && supplier.supplier_id ? supplier.supplier_id : 0);
  return labels[((id % labels.length) + labels.length) % labels.length];
}

function createShopCard({ supplier, index, z }) {
  const group = new THREE.Group();
  group.position.set(0, 0, z);

  const side = index % 2 === 0 ? -1 : 1;
  const x = side * 2.25;
  const y = 2.15;
  const rotY = side * 0.12;

  const bgMat = new THREE.MeshStandardMaterial({ color: 0xffffff, metalness: 0.0, roughness: 0.95 });
  const bg = new THREE.Mesh(new THREE.PlaneGeometry(3.9, 2.45), bgMat);
  bg.position.set(x, y, 0);
  bg.rotation.y = rotY;
  bg.renderOrder = 2;
  group.add(bg);

  const imgMat = new THREE.MeshStandardMaterial({ color: 0xffffff, metalness: 0.0, roughness: 0.9 });
  imgMat.transparent = true;
  imgMat.depthWrite = false;
  const img = new THREE.Mesh(new THREE.PlaneGeometry(3.65, 2.05), imgMat);
  img.position.set(x, y + 0.08, 0.01);
  img.rotation.y = rotY;
  img.renderOrder = 3;
  group.add(img);

  const title = String(supplier.company_name || `Shop ${supplier.supplier_id}`);
  const category = getShopCategoryLabel(supplier);
  const labelTex = makeTextTexture(title, {
    width: 1024,
    height: 256,
    bg: 'rgba(255,255,255,0.0)',
    color: '#0b1020',
    font: '700 72px Inter, Arial',
    subText: category,
    subFont: '500 28px Inter, Arial'
  });
  const labelMat = new THREE.MeshStandardMaterial({ map: labelTex, transparent: true, metalness: 0.0, roughness: 0.95 });
  labelMat.depthWrite = false;
  const label = new THREE.Mesh(new THREE.PlaneGeometry(3.8, 0.95), labelMat);
  label.position.set(x, y - 1.75, 0.01);
  label.rotation.y = rotY;
  label.renderOrder = 4;
  group.add(label);

  const hit = new THREE.Mesh(
    new THREE.PlaneGeometry(4.2, 3.75),
    new THREE.MeshStandardMaterial({ color: 0xffffff, transparent: true, opacity: 0.0 })
  );
  hit.position.set(x, y - 0.25, 0.02);
  hit.rotation.y = rotY;
  hit.userData = { kind: 'shopCard', shopId: supplier.supplier_id };
  hit.material.depthWrite = false;
  hit.material.depthTest = false;
  hit.renderOrder = 10;
  group.add(hit);

  (async () => {
    const bannerTex = await loadTexture(supplier.banner_url);
    const logoTex = await loadTexture(supplier.logo_url);
    const tex = bannerTex || logoTex;
    if (tex) {
      tex.anisotropy = renderer.capabilities.getMaxAnisotropy();
      img.material.map = tex;
      img.material.needsUpdate = true;
    } else {
      const fallback = makeTextTexture(title, {
        width: 1024,
        height: 512,
        bg: 'rgba(255,255,255,1.0)',
        color: '#0b1020',
        font: '700 84px Inter, Arial',
        subText: category,
        subFont: '500 30px Inter, Arial'
      });
      img.material.map = fallback;
      img.material.needsUpdate = true;
    }
  })();

  return { group, hit };
}

function makeWindowPaneTexture() {
  const w = 512;
  const h = 640;
  const cnv = document.createElement('canvas');
  cnv.width = w;
  cnv.height = h;
  const ctx = cnv.getContext('2d');

  // Clear transparent background
  ctx.fillStyle = 'rgba(255, 255, 255, 0.0)';
  ctx.fillRect(0, 0, w, h);

  // Draw window pane grid - 3x4 grid (typical White House window style)
  ctx.strokeStyle = 'rgba(255, 255, 255, 0.9)';
  ctx.lineWidth = 3;

  // Vertical dividers (3 panes across)
  ctx.beginPath();
  ctx.moveTo(w / 3, 0);
  ctx.lineTo(w / 3, h);
  ctx.stroke();
  ctx.beginPath();
  ctx.moveTo((w / 3) * 2, 0);
  ctx.lineTo((w / 3) * 2, h);
  ctx.stroke();

  // Horizontal dividers (4 panes high)
  ctx.beginPath();
  ctx.moveTo(0, h / 4);
  ctx.lineTo(w, h / 4);
  ctx.stroke();
  ctx.beginPath();
  ctx.moveTo(0, (h / 4) * 2);
  ctx.lineTo(w, (h / 4) * 2);
  ctx.stroke();
  ctx.beginPath();
  ctx.moveTo(0, (h / 4) * 3);
  ctx.lineTo(w, (h / 4) * 3);
  ctx.stroke();

  // Add subtle inner frame border
  ctx.strokeStyle = 'rgba(240, 240, 240, 0.6)';
  ctx.lineWidth = 2;
  ctx.strokeRect(8, 8, w - 16, h - 16);

  const tex = new THREE.CanvasTexture(cnv);
  tex.colorSpace = THREE.SRGBColorSpace;
  tex.wrapS = THREE.ClampToEdgeWrapping;
  tex.wrapT = THREE.ClampToEdgeWrapping;
  tex.needsUpdate = true;
  return tex;
}

function makePlasterTexture() {
  const size = 512;
  const cnv = document.createElement('canvas');
  cnv.width = size;
  cnv.height = size;
  const ctx = cnv.getContext('2d');

  const img = ctx.createImageData(size, size);
  for (let i = 0; i < img.data.length; i += 4) {
    const n = 236 + Math.floor((Math.random() - 0.5) * 18);
    img.data[i + 0] = n + 8;
    img.data[i + 1] = n + 2;
    img.data[i + 2] = n;
    img.data[i + 3] = 255;
  }
  ctx.putImageData(img, 0, 0);

  ctx.globalAlpha = 0.08;
  ctx.fillStyle = '#caa2a8';
  ctx.fillRect(0, 0, size, size);
  ctx.globalAlpha = 1;

  const tex = new THREE.CanvasTexture(cnv);
  tex.colorSpace = THREE.SRGBColorSpace;
  tex.wrapS = THREE.RepeatWrapping;
  tex.wrapT = THREE.RepeatWrapping;
  tex.repeat.set(2, 2);
  tex.needsUpdate = true;
  return tex;
}

async function loadSuppliers() {
  try {
    const res = await fetch('./api/suppliers.php', { cache: 'no-store' });
    const data = await res.json();
    if (data && data.success && Array.isArray(data.suppliers)) {
      state.suppliers = data.suppliers;
    } else {
      state.suppliers = [];
    }
  } catch {
    state.suppliers = [];
  }
}

function loadTexture(url) {
  if (!url) return Promise.resolve(null);
  return new Promise((resolve) => {
    textureLoader.load(
      url,
      (t) => {
        t.colorSpace = THREE.SRGBColorSpace;
        resolve(t);
      },
      undefined,
      () => resolve(null)
    );
  });
}

function setHud(title, hint) {
  sceneTitleEl.textContent = title;
  sceneHintEl.textContent = hint;
  sceneTitleEl.style.color = '#000000';
  sceneHintEl.style.color = '#000000';
}

function setFade(on) {
  fadeEl.classList.toggle('on', on);
}

function wait(ms) {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

function clearSceneGeometry() {
  for (let i = scene.children.length - 1; i >= 0; i--) {
    const obj = scene.children[i];
    if (obj.userData && obj.userData.keep) continue;
    if (obj.isLight) continue;
    scene.remove(obj);
  }
  clickable.length = 0;

  hallwayParticles = null;
  hallwayParticlesMeta = null;

  // Clear dreamy elements
  if (scene.userData.dreamyOrbs) {
    scene.userData.dreamyOrbs = null;
  }
  if (scene.userData.dreamyShapes) {
    scene.userData.dreamyShapes = null;
  }
}

ambient.userData.keep = true;
keyLight.userData.keep = true;
fillLight.userData.keep = true;

function createFloor(width, depth) {
  const mesh = new THREE.Mesh(new THREE.BoxGeometry(width, 0.2, depth), floorMat);
  mesh.position.set(0, -0.1, 0);
  scene.add(mesh);
  return mesh;
}

function createFloorWithMaterial(width, depth, material) {
  const mesh = new THREE.Mesh(new THREE.BoxGeometry(width, 0.2, depth), material);
  mesh.position.set(0, -0.1, 0);
  mesh.receiveShadow = true;
  scene.add(mesh);
  return mesh;
}

function createOutside() {
  clearSceneGeometry();
  setHud('Outside', 'Click to ENTER');
  btnBack.hidden = true;

  // Hide search container when outside - make completely invisible
  if (searchContainer) {
    searchContainer.hidden = true;
    searchContainer.style.opacity = '0';
    searchContainer.style.visibility = 'hidden';
  }

  if (overlayEl) overlayEl.hidden = false;

  // Serene, minimalist theme - soft pink-beige sky
  scene.background = new THREE.Color('#f5d4d0');
  scene.fog = new THREE.FogExp2(0xf5e8e9, 0.008);

  // Softer, more diffused lighting for serene aesthetic
  ambient.intensity = 0.92;
  keyLight.intensity = 0.65;
  fillLight.intensity = 0.25;
  keyLight.color = new THREE.Color(0xfff8f8);
  fillLight.color = new THREE.Color(0xf0e8f5);
  keyLight.position.set(12, 20, 12);

  // Softer background wall matching serene theme
  // const bgWall = new THREE.Mesh(
  //   new THREE.PlaneGeometry(70, 32),
  //   new THREE.MeshStandardMaterial({ color: 0xf8ecec, metalness: 0.0, roughness: 1.0 })
  // );
  // bgWall.position.set(0, 12, -45);
  // scene.add(bgWall);

  const rippleNormal = makeRippleNormalTexture();
  // Softer floor material for serene aesthetic
  const luxuryFloorMat = new THREE.MeshPhysicalMaterial({
    color: 0xf0e8e9,
    metalness: 0.0,
    roughness: 0.15,
    clearcoat: 0.8,
    clearcoatRoughness: 0.08,
    normalMap: rippleNormal,
    normalScale: new THREE.Vector2(0.18, 0.18)
  });


  
  const floor = new THREE.Mesh(new THREE.BoxGeometry(70, 0.18, 60), luxuryFloorMat);
  floor.position.set(0, -0.09, -6);
  floor.receiveShadow = true;
  scene.add(floor);

  // --- TEXTURES AND MATERIALS ---
const plasterTex = makePlasterTexture(); // single plaster texture

// Wall materials
const courtyardMat = new THREE.MeshStandardMaterial({
  map: plasterTex,
  color: 0xf3e0e1,
  metalness: 0.0,
  roughness: 1.0,
  side: THREE.DoubleSide
});

const luxuryWallMat = new THREE.MeshStandardMaterial({
  map: plasterTex,
  color: 0xf5e5e6,
  metalness: 0.0,
  roughness: 0.98
});

// Facade panels & accents
const facadePanelMat = new THREE.MeshStandardMaterial({
  color: 0xf9efef,
  metalness: 0.0,
  roughness: 0.94
});

const accentMat = new THREE.MeshStandardMaterial({
  map: plasterTex,
  color: 0xf0d7d9,
  metalness: 0.0,
  roughness: 0.98
});

// --- COURTYARD WALLS ---
const courtyard = new THREE.Group();

const wallBack = new THREE.Mesh(new THREE.PlaneGeometry(120, 50), courtyardMat);
wallBack.position.set(0, 17, -50);
wallBack.receiveShadow = true;
courtyard.add(wallBack);

const wallLeft = new THREE.Mesh(new THREE.PlaneGeometry(120, 50), courtyardMat);
wallLeft.rotation.y = Math.PI / 2;
wallLeft.position.set(-42, 17, -20);
wallLeft.receiveShadow = true;
courtyard.add(wallLeft);

const wallRight = new THREE.Mesh(new THREE.PlaneGeometry(120, 50), courtyardMat);
wallRight.rotation.y = -Math.PI / 2;
wallRight.position.set(42, 17, -20);
wallRight.receiveShadow = true;
courtyard.add(wallRight);

const wallFront = new THREE.Mesh(new THREE.PlaneGeometry(120, 50), courtyardMat);
wallFront.position.set(0, 17, 38);
wallFront.receiveShadow = true;
courtyard.add(wallFront);

scene.add(courtyard);

// --- MALL BUILDING BODY ---
const mallBody = new THREE.Mesh(new THREE.BoxGeometry(18, 20, 7), luxuryWallMat);
mallBody.position.set(0, 4.0, -10.5);
mallBody.castShadow = true;
mallBody.receiveShadow = true;
scene.add(mallBody);

// --- FACADE PANELS ---
for (let i = -2; i <= 2; i++) {
  if (i === 0) continue; // skip middle
  const panel = new THREE.Mesh(new THREE.BoxGeometry(2.2, 7.0, 0.12), facadePanelMat);
  panel.position.set(i * 3.6, 3.8, -7.85);
  panel.castShadow = true;
  panel.receiveShadow = true;
  scene.add(panel);
}

// --- CURVED ACCENTS / CORNICES ---
const cornice = new THREE.Mesh(new THREE.TorusGeometry(12.2, 0.28, 12, 64), accentMat);
cornice.rotation.set(Math.PI / 2, Math.PI / 11, 0);
cornice.position.set(0, 8.15, -10.5);
cornice.castShadow = true;
cornice.receiveShadow = true;
scene.add(cornice);


  const carpetMat = new THREE.MeshStandardMaterial({ color: 0x7a1d2e, metalness: 0.0, roughness: 0.95 });
  const carpet = new THREE.Mesh(new THREE.BoxGeometry(6.4, 0.03, 20.5), carpetMat);
  carpet.position.set(0, 0.02, -3.6);
  carpet.receiveShadow = true;
  scene.add(carpet);

  const carpetTrimMat = new THREE.MeshStandardMaterial({ color: 0xd7b87a, metalness: 0.35, roughness: 0.35 });
  const trimL = new THREE.Mesh(new THREE.BoxGeometry(0.08, 0.035, 20.7), carpetTrimMat);
  trimL.position.set(-3.24, 0.025, -3.6);
  trimL.receiveShadow = true;
  scene.add(trimL);
  const trimR = trimL.clone();
  trimR.position.set(3.24, 0.025, -3.6);
  scene.add(trimR);

  const plaster = makePlasterTexture();
  // Softer wall color matching serene minimalist theme

  // Softer courtyard walls
  wallBack.position.set(0, 17, -50);
  wallBack.receiveShadow = true;
  courtyard.add(wallBack);
  wallLeft.rotation.y = Math.PI / 2;
  wallLeft.position.set(-42, 17, -20);
  wallLeft.receiveShadow = true;
  courtyard.add(wallLeft);
  wallRight.rotation.y = -Math.PI / 2;
  wallRight.position.set(42, 17, -20);
  wallRight.receiveShadow = true;
  courtyard.add(wallRight);
  wallFront.position.set(0, 17, 38);
  wallFront.receiveShadow = true;
  courtyard.add(wallFront);
  scene.add(courtyard);

  mallBody.position.set(0, 4.0, -10.5);
  mallBody.castShadow = true;
  mallBody.receiveShadow = true;
  scene.add(mallBody);

  // Softer trim matching theme
  const trimMat = new THREE.MeshStandardMaterial({ color: 0xf8ecec, metalness: 0.0, roughness: 0.88 });
  const trim = new THREE.Mesh(new THREE.BoxGeometry(19.6, 8.6, 7.3), trimMat);
  trim.position.copy(mallBody.position);
  trim.castShadow = true;
  trim.receiveShadow = true;
  // scene.add(trim);

  // Subtle rounded corners on trim
  const cornerRadius = 0.5;
  const cornerSegments = 24;
  const buildingHeight = 20; // total height of the 2-story building

  const trimCornerGeo = new THREE.CylinderGeometry(cornerRadius, cornerRadius, buildingHeight, cornerSegments);
  const trimCornerMat = new THREE.MeshStandardMaterial({ color: 0xf8ecec, metalness: 0.0, roughness: 0.88 });

  // Front-Left corner
  const trimCornerFL = new THREE.Mesh(trimCornerGeo, trimCornerMat);
  trimCornerFL.position.set(-9.2, buildingHeight / 2, -7.35); // Y = half of building height
  trimCornerFL.rotation.set(0, 0, 0);
  trimCornerFL.castShadow = true;
  scene.add(trimCornerFL);


  const trimCornerFR = trimCornerFL.clone();
  trimCornerFR.position.set(9.2, 4.0, -7.35);
  scene.add(trimCornerFR);

  // Back corners
  const trimCornerBL = trimCornerFL.clone();
  trimCornerBL.position.set(-9.2, 4.0, -13.65);
  scene.add(trimCornerBL);

  const trimCornerBR = trimCornerFL.clone();
  trimCornerBR.position.set(9.2, 4.0, -13.65);
  scene.add(trimCornerBR);

  const radiusMat = new THREE.MeshStandardMaterial({ color: 0xf7eaea, metalness: 0.0, roughness: 0.90 });
  const cornerGeo = new THREE.CylinderGeometry(0.48, 0.48, 8.6, 28, 1, false, 0, Math.PI / 2);
  const cornerFL = new THREE.Mesh(cornerGeo, radiusMat);
  cornerFL.position.set(-9.8, 4.0, -7.75);
  cornerFL.rotation.y = Math.PI;
  cornerFL.castShadow = true;
  cornerFL.receiveShadow = true;
  scene.add(cornerFL);
  const cornerFR = cornerFL.clone();
  cornerFR.position.set(9.8, 5.0, -7.75);
  cornerFR.rotation.y = -Math.PI / 2;
  // scene.add(cornerFR);

  for (let i = -2; i <= 2; i++) {
    if (i === 0) continue;
    const panel = new THREE.Mesh(new THREE.BoxGeometry(2.2, 7.0, 0.12), facadePanelMat);
    panel.position.set(i * 3.6, 3.8, -7.85);
    panel.castShadow = true;
    panel.receiveShadow = true;
    scene.add(panel);
  }

  // Subtle curved elements for dreamy aesthetic

  // Subtle curved cornice (gentle arch)

  cornice.rotation.set(
    Math.PI / 2,    // stand up
    Math.PI / 11,   // horizontal tilt
    0
  );
  cornice.position.set(0, 8.15, -10.5);
  cornice.castShadow = true;
  cornice.receiveShadow = true;
  scene.add(cornice);


  function addLuxuryCornice({
    x = 0,
    y = 8.15,
    z = -10.5,
    radius = 12.2,
    tube = 0.28,
    radialSegments = 24,
    tubularSegments = 128,
    rotationX = Math.PI / 2,
    rotationY = Math.PI / 11,
    rotationZ = 0,
    fasciaWidth = 19.8,
    fasciaHeight = 0.75,
    fasciaDepth = 0.8,
    material = accentMat
  }) {
    // Cornice torus (smooth)
    const corniceGeo = new THREE.TorusGeometry(
      radius,
      tube,
      radialSegments,
      tubularSegments
    );
    corniceGeo.computeVertexNormals(); // ensures smooth shading

    const cornice = new THREE.Mesh(corniceGeo, material);
    cornice.rotation.set(rotationX, rotationY, rotationZ);
    cornice.position.set(x, y, z);
    cornice.castShadow = true;
    cornice.receiveShadow = true;
    scene.add(cornice);

    // Optional fascia below cornice
    const corniceFascia = new THREE.Mesh(
      new THREE.BoxGeometry(fasciaWidth, fasciaHeight, fasciaDepth),
      material
    );
    corniceFascia.position.set(x, y - 0.15, z + 2.35);
    corniceFascia.castShadow = true;
    corniceFascia.receiveShadow = true;
    scene.add(corniceFascia);

    return { cornice, corniceFascia };
  }

  const sideButtressL = new THREE.Mesh(new THREE.BoxGeometry(1.0, 6.0, 1.1), accentMat);
  sideButtressL.position.set(-9.55, 4.8, -10.5);
  sideButtressL.castShadow = true;
  sideButtressL.receiveShadow = true;
  scene.add(sideButtressL);

  const sideButtressR = sideButtressL.clone();
  sideButtressR.position.set(9.55, 4.8, -10.5);
  scene.add(sideButtressR);

  // Dreamy aesthetic elements
  // Floating translucent orbs
  const orbMat = new THREE.MeshPhysicalMaterial({
    color: 0xff1493,
    metalness: 0.0,
    roughness: 0.1,
    transmission: 0.9,
    thickness: 0.5,
    transparent: true,
    opacity: 0.3,
    emissive: 0xffe8f0,
    emissiveIntensity: 0.2
  });

  // Create floating orbs around the mall
  const orbPositions = [
    { x: -24, y: 8, z: -6 },
    { x: 24, y: 13, z: -7 },
    { x: -18, y: 5, z: -11 },
    { x: 18, y: 10, z: -12 },
    { x: -12, y: 12, z: -9 },
    { x: 12, y: 6, z: -10 },
    { x: -21, y: 9, z: -14 },
    { x: 21, y: 7, z: -13 },
    // { x: -16, y: 4, z: -8 },
    { x: 16, y: 14, z: -9 }
  ];


  const floatingOrbs = [];
  orbPositions.forEach((pos, i) => {
    const size = 0.4 + Math.random() * 0.3;
    const orb = new THREE.Mesh(new THREE.SphereGeometry(size, 16, 16), orbMat.clone());
    orb.position.set(pos.x, pos.y, pos.z);
    orb.userData = {
      baseY: pos.y,
      speed: 0.3 + Math.random() * 0.2,
      phase: Math.random() * Math.PI * 2,
      rotationSpeed: (Math.random() - 0.5) * 0.02
    };
    orb.castShadow = false;
    scene.add(orb);
    floatingOrbs.push(orb);
  });

  // Floating geometric shapes
  const geometricMat = new THREE.MeshPhysicalMaterial({
    color: 0xff1493,        // base color
    metalness: 0.25,        // slightly more metallic → reflects more
    roughness: 0.05,        // low roughness → smooth glossy surface
    transmission: 0.7,      // allows light to pass through (glass-like)
    thickness: 0.3,
    transparent: true,
    opacity: 0.4,
    clearcoat: 1.0,         // adds extra glossy layer
    clearcoatRoughness: 0.05,
    reflectivity: 0.9,      // stronger reflection
    emissive: 0xffe8f5,     // subtle glow
    emissiveIntensity: 0.15
  });


  const floatingShapes = [];
  const shapePositions = [
    { x: -25, y: 6, z: -5, type: 'box' },
    { x: 25, y: 12, z: -6, type: 'octa' },
    { x: -20, y: 9, z: -12, type: 'tetra' },
    { x: 20, y: 7, z: -14, type: 'box' },
    { x: -15, y: 11, z: -10, type: 'octa' },
    { x: 15, y: 5, z: -13, type: 'tetra' },
    { x: -22, y: 14, z: -8, type: 'box' },
    { x: 22, y: 4.5, z: -9, type: 'octa' }
  ];



  shapePositions.forEach((pos) => {
    let geometry;
    if (pos.type === 'box') {
      geometry = new THREE.BoxGeometry(0.5, 0.5, 0.5);
    } else if (pos.type === 'octa') {
      geometry = new THREE.OctahedronGeometry(0.4);
    } else if (pos.type === 'tetra') {
      geometry = new THREE.TetrahedronGeometry(0.35);
    }

    const shape = new THREE.Mesh(geometry, geometricMat.clone());
    shape.position.set(pos.x, pos.y, pos.z);
    shape.castShadow = true;
    shape.userData = {
      baseY: pos.y,
      speed: 0.25 + Math.random() * 0.15,
      phase: Math.random() * Math.PI * 2,
      rotationSpeed: (Math.random() - 0.5) * 0.03
    };
    shape.receiveShadow = true;
    scene.add(shape);
    floatingShapes.push(shape);
  });


  // Soft ambient glow around the mall
  const glowMat = new THREE.MeshStandardMaterial({
    color: 0xfff0f5,
    emissive: 0xffe8f0,
    emissiveIntensity: 0.3,
    transparent: true,
    opacity: 0.15,
    side: THREE.DoubleSide
  });

  const ambientGlow = new THREE.Mesh(new THREE.SphereGeometry(25, 32, 32), glowMat);
  ambientGlow.position.set(0, 5, -10);
  ambientGlow.scale.set(1, 0.6, 1);
  ambientGlow.renderOrder = -1;
  scene.add(ambientGlow);

  // Store dreamy elements for animation
  scene.userData.dreamyOrbs = floatingOrbs;
  scene.userData.dreamyShapes = floatingShapes;

  // Minimalist flat roof matching mall aesthetic
  const mallTop = 8.0; // Top of mall at y = 4.0 + 4.0
  const overhang = 2; // Subtle overhang for minimalist look

  // Roof material matching mall's soft pink/beige aesthetic
  const roofMat = new THREE.MeshStandardMaterial({
    map: plaster,
    color: 0x643e47,
    metalness: 0.0,
    roughness: 0.95
  });

  // Main flat roof slab - clean and minimalist
  const roofSlab = new THREE.Mesh(new THREE.BoxGeometry(18 + overhang * 2, 0.25, 7 + overhang * 2), roofMat);
  roofSlab.position.set(0, mallTop + 0.125, -10.5);
  roofSlab.castShadow = true;
  roofSlab.receiveShadow = true;
  scene.add(roofSlab);

  // Subtle roof edge/fascia - slightly lighter tone with rounded corners
  const roofEdgeMat = new THREE.MeshStandardMaterial({
    map: plaster,
    color: 0xf5e5e6,  // Slightly lighter pink-beige
    metalness: 0.0,
    roughness: 0.92
  });

  // Front edge
  const frontEdge = new THREE.Mesh(new THREE.BoxGeometry(18 + overhang * 2, 0.12, 0.15), roofEdgeMat);
  frontEdge.position.set(0, mallTop + 0.125, -6.85 - overhang);
  frontEdge.castShadow = true;
  scene.add(frontEdge);

  // Rounded front edge corners
  const frontEdgeCornerL = new THREE.Mesh(new THREE.CylinderGeometry(0.075, 0.075, 0.15, 16), roofEdgeMat);
  frontEdgeCornerL.rotation.z = Math.PI / 2;
  frontEdgeCornerL.position.set(-9.6 - overhang, mallTop + 0.125, -6.85 - overhang);
  scene.add(frontEdgeCornerL);

  const frontEdgeCornerR = frontEdgeCornerL.clone();
  frontEdgeCornerR.position.set(9.6 + overhang, mallTop + 0.125, -6.85 - overhang);
  scene.add(frontEdgeCornerR);

  // Back edge
  const backEdge = new THREE.Mesh(new THREE.BoxGeometry(18 + overhang * 2, 0.12, 0.15), roofEdgeMat);
  backEdge.position.set(0, mallTop + 0.125, -14.15 + overhang);
  backEdge.castShadow = true;
  scene.add(backEdge);

  // Rounded back edge corners
  const backEdgeCornerL = frontEdgeCornerL.clone();
  backEdgeCornerL.position.set(-9.6 - overhang, mallTop + 0.125, -14.15 + overhang);
  scene.add(backEdgeCornerL);

  const backEdgeCornerR = frontEdgeCornerL.clone();
  backEdgeCornerR.position.set(9.6 + overhang, mallTop + 0.125, -14.15 + overhang);
  scene.add(backEdgeCornerR);

  // Softer entry cut matching serene theme
  const cutMat = new THREE.MeshStandardMaterial({ map: plaster, color: 0xf8ecec, metalness: 0.0, roughness: 0.98 });
  const entryCut = new THREE.Mesh(new THREE.BoxGeometry(6.6, 6.8, 0.9), cutMat);
  entryCut.position.set(0, 2.5, -9.9);
  entryCut.castShadow = true;
  entryCut.receiveShadow = true;
  scene.add(entryCut);

  // Softer inner glow - warm but muted
  const innerGlow = new THREE.Mesh(
    new THREE.PlaneGeometry(5.4, 5.9),
    new THREE.MeshStandardMaterial({ color: 0xfff5e8, emissive: 0xfff5e8, emissiveIntensity: 0.35, transparent: true, opacity: 0.15 })
  );
  innerGlow.position.set(0, 2.4, -10.28);
  innerGlow.renderOrder = 0;
  scene.add(innerGlow);

  const signTex = makeTextTexture('Malltiverse', {
    width: 1024,
    height: 256,
    bg: 'rgba(255,255,255,0.0)',
    color: '#e9eefc',
    font: '600 84px Inter, Arial',
    subText: 'VIRTUAL MALL',
    subFont: '500 30px Inter, Arial'
  });
  const signMat = new THREE.MeshStandardMaterial({ map: signTex, transparent: true, emissive: new THREE.Color(0xffffff), emissiveIntensity: 0.12 });
  signMat.depthWrite = false;
  const sign = new THREE.Mesh(new THREE.PlaneGeometry(11.2, 2.2), signMat);
  sign.position.set(0, 7.0, -7.01);
  scene.add(sign);

  const plaqueTex = makeTextTexture('LUXURY BRANDS', {
    width: 768,
    height: 256,
    bg: 'rgba(255,255,255,0.0)',
    color: '#0b1020',
    font: '600 54px Inter, Arial',
    subText: 'Shoes • Clothing • Accessories',
    subFont: '500 22px Inter, Arial'
  });
  const plaqueMat = new THREE.MeshStandardMaterial({ map: plaqueTex, transparent: true, metalness: 0.0, roughness: 0.95 });
  plaqueMat.depthWrite = false;
  const plaque = new THREE.Mesh(new THREE.PlaneGeometry(6.6, 1.8), plaqueMat);
  plaque.position.set(-5.9, 2.5, -7.02);
  scene.add(plaque);

  const vTex = makeTextTexture('MALL', {
    width: 512,
    height: 1024,
    bg: 'rgba(255,255,255,1.0)',
    color: '#0b1020',
    font: '700 120px Inter, Arial',
    subText: 'ENTER',
    subFont: '600 44px Inter, Arial'
  });
  const vMat = new THREE.MeshStandardMaterial({ map: vTex, metalness: 0.0, roughness: 0.9 });
  const vSign = new THREE.Mesh(new THREE.PlaneGeometry(1.0, 2.2), vMat);
  vSign.position.set(7.9, 3.0, -7.05);
  scene.add(vSign);

  const surroundMat = new THREE.MeshStandardMaterial({ color: 0xf8fafc, metalness: 0.0, roughness: 0.82 });
  const surroundZ = -6.66;
  const surroundTop = new THREE.Mesh(new THREE.BoxGeometry(3.9, 0.22, 0.14), surroundMat);
  surroundTop.position.set(0, 5.55, surroundZ);
  surroundTop.castShadow = true;
  surroundTop.receiveShadow = true;
  scene.add(surroundTop);
  const surroundL = new THREE.Mesh(new THREE.BoxGeometry(0.22, 5.35, 0.14), surroundMat);
  surroundL.position.set(-1.95, 2.88, surroundZ);
  surroundL.castShadow = true;
  surroundL.receiveShadow = true;
  scene.add(surroundL);
  const surroundR = surroundL.clone();
  surroundR.position.set(1.95, 2.88, surroundZ);
  scene.add(surroundR);
  const surroundBot = new THREE.Mesh(new THREE.BoxGeometry(3.6, 0.18, 0.14), surroundMat);
  surroundBot.position.set(0, 0.52, surroundZ);
  surroundBot.castShadow = true;
  surroundBot.receiveShadow = true;
  scene.add(surroundBot);

  const doorWoodTex = makeWoodTexture();
  doorWoodTex.repeat.set(1.6, 1.05);
  doorWoodTex.anisotropy = renderer.capabilities.getMaxAnisotropy();
  const doorWoodBump = makeWoodBumpTexture();
  doorWoodBump.repeat.copy(doorWoodTex.repeat);
  doorWoodBump.anisotropy = doorWoodTex.anisotropy;
  // Enhanced wood material with richer colors and better properties for realistic wood appearance
  const doorWoodMat = new THREE.MeshStandardMaterial({
    map: doorWoodTex,
    bumpMap: doorWoodBump,
    bumpScale: 0.12,  // Increased bump for more pronounced wood grain
    color: 0x8b5a3c,  // Rich brown wood color
    metalness: 0.0,   // Wood has no metalness
    roughness: 0.75,  // Slightly rougher for natural wood texture
    emissive: 0x0a0602,
    emissiveIntensity: 0.01
  });
  doorWoodMat.depthWrite = true;
  doorWoodMat.depthTest = true;
  const doorFrameMat = new THREE.MeshStandardMaterial({ color: 0x101827, metalness: 0.25, roughness: 0.55 });
  doorFrameMat.polygonOffset = true;
  doorFrameMat.polygonOffsetFactor = -1;
  doorFrameMat.polygonOffsetUnits = -1;

  const doorGroup = new THREE.Group();
  doorGroup.position.set(0, 0, 0);
  scene.add(doorGroup);

  const doorLeaf = new THREE.Mesh(new THREE.BoxGeometry(2.62, 4.32, 0.18), doorWoodMat);
  doorLeaf.position.set(0, 2.8, -6.64);  // Moved forward to be in front of surround (-6.66) so wood texture is visible
  doorLeaf.castShadow = true;
  doorLeaf.receiveShadow = true;
  doorLeaf.renderOrder = 1;  // Render order to ensure visibility
  doorLeaf.userData = { kind: 'mallDoor' };
  doorGroup.add(doorLeaf);
  clickable.push(doorLeaf);

  const frameZ = -6.88;
  const frameTop = new THREE.Mesh(new THREE.BoxGeometry(2.4, 0.12, 0.06), doorFrameMat);
  frameTop.position.set(0, 4.88, frameZ);
  frameTop.castShadow = true;
  frameTop.receiveShadow = true;
  frameTop.renderOrder = 3;
  doorGroup.add(frameTop);
  const frameBot = frameTop.clone();
  frameBot.position.set(0, 0.72, frameZ);
  frameBot.renderOrder = 3;
  doorGroup.add(frameBot);
  const frameL = new THREE.Mesh(new THREE.BoxGeometry(0.12, 4.18, 0.06), doorFrameMat);
  frameL.position.set(-1.14, 2.8, frameZ);
  frameL.castShadow = true;
  frameL.receiveShadow = true;
  frameL.renderOrder = 3;
  doorGroup.add(frameL);
  const frameR = frameL.clone();
  frameR.position.set(1.14, 2.8, frameZ);
  frameR.renderOrder = 3;
  doorGroup.add(frameR);

  const glassPaneMat = new THREE.MeshPhysicalMaterial({ color: 0xdfe8ff, metalness: 0.0, roughness: 0.32, transmission: 0.52, thickness: 0.7, transparent: true, opacity: 0.08 });
  glassPaneMat.depthWrite = false;
  glassPaneMat.depthTest = true;
  glassPaneMat.polygonOffset = true;
  glassPaneMat.polygonOffsetFactor = -3;
  glassPaneMat.polygonOffsetUnits = -3;
  const glassZ = -6.65;  // Positioned just in front of the door to show glass panes
  const glassTop = new THREE.Mesh(new THREE.PlaneGeometry(1.55, 1.2), glassPaneMat);
  glassTop.position.set(0, 3.72, glassZ);
  glassTop.renderOrder = 10;  // Higher render order to render on top
  doorGroup.add(glassTop);
  const glassMid = new THREE.Mesh(new THREE.PlaneGeometry(1.55, 0.95), glassPaneMat);
  glassMid.position.set(0, 2.55, glassZ);
  glassMid.renderOrder = 10;  // Higher render order to render on top
  doorGroup.add(glassMid);

  const rail = new THREE.Mesh(new THREE.BoxGeometry(1.95, 0.12, 0.05), doorFrameMat);
  rail.position.set(0, 3.05, frameZ);
  rail.castShadow = true;
  rail.receiveShadow = true;
  doorGroup.add(rail);

  const pillarMat = new THREE.MeshStandardMaterial({ color: 0xf8fafc, metalness: 0.0, roughness: 0.78 });
  const pillarCapMat = new THREE.MeshStandardMaterial({ color: 0xffffff, metalness: 0.0, roughness: 0.72 });
  const makePillar = (x) => {
    const g = new THREE.Group();
    const base = new THREE.Mesh(new THREE.CylinderGeometry(0.48, 0.56, 0.45, 24), pillarCapMat);
    base.position.set(x, 0.23, -6.98);
    base.castShadow = true;
    base.receiveShadow = true;
    g.add(base);
    const shaft = new THREE.Mesh(new THREE.CylinderGeometry(0.34, 0.38, 4.9, 28), pillarMat);
    shaft.position.set(x, 2.75, -6.98);
    shaft.castShadow = true;
    shaft.receiveShadow = true;
    g.add(shaft);
    const capital = new THREE.Mesh(new THREE.CylinderGeometry(0.52, 0.44, 0.48, 24), pillarCapMat);
    capital.position.set(x, 5.35, -6.98);
    capital.castShadow = true;
    capital.receiveShadow = true;
    g.add(capital);
    const ring = new THREE.Mesh(new THREE.TorusGeometry(0.39, 0.05, 10, 24), pillarCapMat);
    ring.rotation.x = Math.PI / 2;
    ring.position.set(x, 4.95, -6.98);
    ring.castShadow = true;
    g.add(ring);
    scene.add(g);
  };
  makePillar(-2.65);
  makePillar(2.65);

  const makeOverhangRoof = () => {
    const roofGroup = new THREE.Group();

    const roofWidth = 7;   // X direction (span between pillars)
    const roofDepth = 2;   // Z direction (overhang in front/back)
    const roofThickness = 0.3;

    const roofMat = new THREE.MeshStandardMaterial({ color: 0xe0e0e0, metalness: 0.0, roughness: 0.6 });

    // Create main roof slab
    const roof = new THREE.Mesh(new THREE.BoxGeometry(roofWidth, roofThickness, roofDepth), roofMat);
    roof.position.set(0, 5.7, -6.98); // slightly above the pillars
    roof.castShadow = true;
    roof.receiveShadow = true;
    roofGroup.add(roof);

    // Optional: small bevel or trim around edges
    const trim = new THREE.Mesh(new THREE.BoxGeometry(roofWidth + 0.1, 0.05, roofDepth + 0.1), roofMat);
    trim.position.set(0, 5.95, -6.98);
    roofGroup.add(trim);

    scene.add(roofGroup);
  };

  makeOverhangRoof();

  const handleMat = new THREE.MeshStandardMaterial({ color: 0xffff00, metalness: 0.7, roughness: 0.2, emissive: 0xffeb3b, emissiveIntensity: 0.3 });
  handleMat.polygonOffset = true;
  handleMat.polygonOffsetFactor = -2;
  handleMat.polygonOffsetUnits = -2;
  const handleZ = -6.76;
  const handleBar = new THREE.Mesh(new THREE.CylinderGeometry(0.03, 0.03, 0.9, 14), handleMat);
  handleBar.rotation.z = Math.PI / 2;
  handleBar.position.set(0.72, 2.85, handleZ);
  handleBar.castShadow = true;
  handleBar.receiveShadow = true;
  handleBar.renderOrder = 6;
  doorGroup.add(handleBar);
  const handleBar2 = handleBar.clone();
  handleBar2.position.set(-0.72, 2.85, handleZ);
  doorGroup.add(handleBar2);
  const handleMount = new THREE.Mesh(new THREE.CylinderGeometry(0.06, 0.06, 0.12, 16), handleMat);
  handleMount.rotation.x = Math.PI / 2;
  handleMount.position.set(0.72, 2.85, handleZ - 0.02);
  handleMount.castShadow = true;
  handleMount.receiveShadow = true;
  handleMount.renderOrder = 6;
  doorGroup.add(handleMount);
  const handleMount2 = handleMount.clone();
  handleMount2.position.set(-0.72, 2.85, handleZ - 0.02);
  doorGroup.add(handleMount2);

  const windowMat = new THREE.MeshPhysicalMaterial({
    color: 0xd0e8ff,  // Light blue tint to make windows more visible
    metalness: 0.0,
    roughness: 0.02,
    transmission: 0.88,
    thickness: 0.5,
    transparent: true,
    opacity: 0.75,
    emissive: 0x4a90e2,
    emissiveIntensity: 0.15
  });
  windowMat.depthWrite = true;
  windowMat.depthTest = true;
  windowMat.polygonOffset = true;
  windowMat.polygonOffsetFactor = -8;
  windowMat.polygonOffsetUnits = -8;

  const winFrameMat = new THREE.MeshStandardMaterial({ color: 0xffffff, metalness: 0.1, roughness: 0.25 });
  winFrameMat.polygonOffset = true;
  winFrameMat.polygonOffsetFactor = -3;
  winFrameMat.polygonOffsetUnits = -3;

  // Create window group with frame and single glass plane
  // Make sure the tracking array exists
  if (!scene.userData.windows) scene.userData.windows = [];

  const createWindow = (x, floor = 1) => {
    const winGroup = new THREE.Group();

    // Floor Y positions
    const floorY = { 1: 4.0, 2: 12 };

    // Window frame dimensions
    const frameThickness = 0.2;
    const frameDepth = 0.15;
    const frameWidth = 3.5;
    const frameHeight = 4.2;
    const glassWidth = 3.05;
    const glassHeight = 3.65;

    // Frame material
    const winFrameMat = new THREE.MeshStandardMaterial({ color: 0x333333, roughness: 0.6, metalness: 0.3 });

    // Glass material
    const windowMat = new THREE.MeshStandardMaterial({ color: 0x99ccff, transparent: true, opacity: 0.6 });

    // Top frame
    const frameTop = new THREE.Mesh(new THREE.BoxGeometry(frameWidth, frameThickness, frameDepth), winFrameMat);
    frameTop.position.set(0, frameHeight / 2 - frameThickness / 2, 0.1);
    frameTop.castShadow = true;
    frameTop.renderOrder = 2;
    winGroup.add(frameTop);

    // Bottom frame
    const frameBottom = new THREE.Mesh(new THREE.BoxGeometry(frameWidth, frameThickness, frameDepth), winFrameMat);
    frameBottom.position.set(0, -frameHeight / 2 + frameThickness / 2, 0.1);
    frameBottom.castShadow = true;
    frameBottom.renderOrder = 2;
    winGroup.add(frameBottom);

    // Left frame
    const frameLeft = new THREE.Mesh(new THREE.BoxGeometry(frameThickness, frameHeight, frameDepth), winFrameMat);
    frameLeft.position.set(-frameWidth / 2 + frameThickness / 2, 0, 0.1);
    frameLeft.castShadow = true;
    frameLeft.renderOrder = 2;
    winGroup.add(frameLeft);

    // Right frame
    const frameRight = new THREE.Mesh(new THREE.BoxGeometry(frameThickness, frameHeight, frameDepth), winFrameMat);
    frameRight.position.set(frameWidth / 2 - frameThickness / 2, 0, 0.1);
    frameRight.castShadow = true;
    frameRight.renderOrder = 2;
    winGroup.add(frameRight);

    // Glass
    const glassPlane = new THREE.Mesh(new THREE.PlaneGeometry(glassWidth, glassHeight), windowMat);
    glassPlane.position.set(0, 0, 0.12);
    glassPlane.renderOrder = 1;
    winGroup.add(glassPlane);

    // Mullion material
    const mullionMat = new THREE.MeshStandardMaterial({ color: 0xffffff, metalness: 0.1, roughness: 0.3 });
    mullionMat.polygonOffset = true;
    mullionMat.polygonOffsetFactor = -3;
    mullionMat.polygonOffsetUnits = -3;

    // Vertical mullions (2 dividers for 3 columns)
    for (let i = 1; i < 3; i++) {
      const xPos = -glassWidth / 2 + (glassWidth / 3) * i;
      const mullionV = new THREE.Mesh(new THREE.BoxGeometry(0.05, glassHeight, 0.02), mullionMat);
      mullionV.position.set(xPos, 0, 0.06);
      mullionV.castShadow = false;
      mullionV.renderOrder = 3;
      winGroup.add(mullionV);
    }

    // Horizontal mullions (3 dividers for 4 rows)
    for (let i = 1; i < 4; i++) {
      const yPos = glassHeight / 2 - (glassHeight / 4) * i;
      const mullionH = new THREE.Mesh(new THREE.BoxGeometry(glassWidth, 0.05, 0.02), mullionMat);
      mullionH.position.set(0, yPos, 0.06);
      mullionH.castShadow = false;
      mullionH.renderOrder = 3;
      winGroup.add(mullionH);
    }

    // Set position based on floor
    winGroup.position.set(x, floorY[floor], -7);
    scene.add(winGroup);

    // Track window
    scene.userData.windows.push(winGroup);
  };

  // --- USAGE EXAMPLES ---
  createWindow(-6.2);        // First floor
  createWindow(6.2);         // First floor
  createWindow(-6.2, 2);     // Second floor
  createWindow(6.2, 2);      // Second floor


  const stairMat = new THREE.MeshStandardMaterial({ color: 0xf3eeef, metalness: 0.0, roughness: 0.92 });
  for (let i = 0; i < 7; i++) {
    const step = new THREE.Mesh(new THREE.BoxGeometry(9.6, 0.28, 1.2), stairMat);
    step.position.set(0, 0.14 + i * 0.28, -2.2 - i * 1.05);
    step.receiveShadow = true;
    step.castShadow = true;
    scene.add(step);
  }

  const planterMat = new THREE.MeshStandardMaterial({ color: 0xf7eded, metalness: 0.0, roughness: 0.9 });
  const soilMat = new THREE.MeshStandardMaterial({ color: 0x2a1a16, metalness: 0.0, roughness: 1.0 });
  const leafMat = new THREE.MeshStandardMaterial({ color: 0x1f6f4a, metalness: 0.0, roughness: 0.85 });

  const makePlanter = (x, z) => {
    const g = new THREE.Group();
    const pod = new THREE.Mesh(new THREE.CylinderGeometry(0.75, 0.9, 0.55, 24), planterMat);
    pod.position.set(x, 0.28, z);
    pod.castShadow = true;
    pod.receiveShadow = true;
    g.add(pod);
    const soil = new THREE.Mesh(new THREE.CylinderGeometry(0.62, 0.72, 0.18, 20), soilMat);
    soil.position.set(x, 0.48, z);
    soil.receiveShadow = true;
    g.add(soil);
    for (let i = 0; i < 7; i++) {
      const leaf = new THREE.Mesh(new THREE.ConeGeometry(0.16 + Math.random() * 0.08, 0.85 + Math.random() * 0.55, 10), leafMat);
      leaf.position.set(x + (Math.random() - 0.5) * 0.45, 0.75 + Math.random() * 0.45, z + (Math.random() - 0.5) * 0.45);
      leaf.rotation.z = (Math.random() - 0.5) * 0.5;
      leaf.rotation.x = (Math.random() - 0.5) * 0.4;
      leaf.castShadow = true;
      g.add(leaf);
    }
    scene.add(g);
  };

  const planterZs = [-2.8, -4.5, -6.2, -7.9, -9.6];
  for (let i = 0; i < planterZs.length; i++) {
    makePlanter(-5.8, planterZs[i]);
    makePlanter(5.8, planterZs[i]);
  }

  const archMat = new THREE.MeshStandardMaterial({ map: plaster, color: 0xf2d9da, metalness: 0.0, roughness: 0.98 });
  const archPillarGeo = new THREE.BoxGeometry(1.1, 9.6, 1.0);
  const archTopGeo = new THREE.TorusGeometry(4.6, 0.42, 16, 64, Math.PI);
  const archOffsets = [-10.5, 10.5];
  for (let i = 0; i < archOffsets.length; i++) {
    const ax = archOffsets[i];
    const p1 = new THREE.Mesh(archPillarGeo, archMat);
    p1.position.set(ax, 4.8, -14);
    scene.add(p1);
    const p2 = new THREE.Mesh(archPillarGeo, archMat);
    p2.position.set(ax + (ax < 0 ? 6.8 : -6.8), 4.8, -14);
    scene.add(p2);
    const top = new THREE.Mesh(archTopGeo, archMat);
    top.rotation.x = Math.PI / 2;
    top.rotation.z = ax < 0 ? Math.PI : 0;
    top.position.set(ax + (ax < 0 ? 3.4 : -3.4), 9.6, -14);
    scene.add(top);
  }

  camera.position.set(0, 2.05, 10);
  state.cameraTargetPos.set(0, 2.05, 10);
  state.cameraLookAt.set(0, 3.3, -8.5);
  state.cameraTargetLookAt.set(0, 3.3, -8.5);
}

function createHallway() {
  clearSceneGeometry();
  const { slice, total } = getSuppliersForFloor();
  const totalFloors = Math.max(1, Math.ceil(total / state.shopsPerFloor));
  setHud('Mall Hallway', `Scroll to move forward. Click a shop card to enter. Floor ${state.floorIndex + 1}/${totalFloors}`);
  btnBack.hidden = false;
  const hallWidth = 20;


  // Show search container and update floor selector
  if (searchContainer) {
    searchContainer.hidden = false;
    searchContainer.style.opacity = '1';
    searchContainer.style.visibility = 'visible';
  }
  if (floorSelector) {
    floorSelector.innerHTML = '';
    for (let i = 0; i < totalFloors; i++) {
      const option = document.createElement('option');
      option.value = i;
      option.textContent = `Floor ${i + 1}`;
      if (i === state.floorIndex) option.selected = true;
      floorSelector.appendChild(option);
    }
  }

  if (overlayEl) overlayEl.hidden = true;

  scene.background = new THREE.Color('#f7f7f5');
  scene.fog = new THREE.FogExp2(0xf7f7f5, 0.038);

  ambient.intensity = 0.92;
  keyLight.intensity = 0.38;
  fillLight.intensity = 0.12;
  keyLight.color = new THREE.Color(0xfff3ea);
  fillLight.color = new THREE.Color(0xe7efff);

  const shopsThisFloor = slice.length;
  const cards = Math.max(1, shopsThisFloor);
  const cardSpacing = 11;
  const startZ = 2;
  const firstCardZ = -6;
  const lastCardZ = firstCardZ - (cards - 1) * cardSpacing;
  const endZ = lastCardZ - 18;
  const depth = Math.abs(endZ - startZ) + 10;

  createFloorWithMaterial(hallWidth, depth, hallwayFloorMat).position.z = (startZ + endZ) / 2;

  {
    const carpetLen = Math.abs(endZ - startZ) + 6;
    const carpetMat = new THREE.MeshStandardMaterial({ color: 0x7a1d2e, metalness: 0.0, roughness: 0.96 });
    const carpet = new THREE.Mesh(new THREE.BoxGeometry(3.4, 0.028, carpetLen), carpetMat);
    carpet.position.set(0, 0.015, (startZ + endZ) / 2);
    carpet.receiveShadow = true;
    scene.add(carpet);

    const trimMat = new THREE.MeshStandardMaterial({ color: 0xd7b87a, metalness: 0.35, roughness: 0.35 });
    const trimL = new THREE.Mesh(new THREE.BoxGeometry(0.06, 0.03, carpetLen + 0.2), trimMat);
    trimL.position.set(-1.72, 0.016, (startZ + endZ) / 2);
    trimL.receiveShadow = true;
    scene.add(trimL);
    const trimR = trimL.clone();
    trimR.position.set(1.72, 0.016, (startZ + endZ) / 2);
    scene.add(trimR);
  }

  const leftWall = new THREE.Mesh(new THREE.BoxGeometry(0.35, 4.6, depth), hallwayWallMat);
  leftWall.position.set(-5.75, 2.3, (startZ + endZ) / 2);
  leftWall.castShadow = true;
  leftWall.receiveShadow = true;
  scene.add(leftWall);

  const rightWall = new THREE.Mesh(new THREE.BoxGeometry(0.35, 4.6, depth), hallwayWallMat);
  rightWall.position.set(5.75, 2.3, (startZ + endZ) / 2);
  rightWall.castShadow = true;
  rightWall.receiveShadow = true;
  scene.add(rightWall);

  const ceiling = new THREE.Mesh(new THREE.BoxGeometry(12, 0.3, depth), hallwayWallMat);
  ceiling.position.set(0, 4.7, (startZ + endZ) / 2);
  ceiling.castShadow = true;
  ceiling.receiveShadow = true;
  scene.add(ceiling);

  const endCap = new THREE.Mesh(new THREE.BoxGeometry(12, 4.6, 0.35), hallwayWallMat);
  endCap.position.set(0, 2.3, endZ - 1.5);
  endCap.castShadow = true;
  endCap.receiveShadow = true;
  scene.add(endCap);

  const panelStep = 9;
  const panelCount = Math.max(2, Math.ceil((Math.abs(endZ - startZ) + 10) / panelStep));
  for (let i = 0; i < panelCount; i++) {
    const z = startZ - i * panelStep;
    const panel = new THREE.Mesh(new THREE.PlaneGeometry(8.8, 1.6), ceilingLightMat);
    panel.rotation.x = Math.PI / 2;
    panel.position.set(0, 4.56, z);
    panel.material = ceilingLightMat.clone();
    panel.material.emissive = new THREE.Color(i % 2 === 0 ? 0xffe7e7 : 0xe7f0ff);
    panel.material.emissiveIntensity = 0.16;
    scene.add(panel);
  }

  {
    const gradTex = makeTextTexture('', {
      width: 1024,
      height: 1024,
      bg: 'rgba(0,0,0,0.0)'
    });
    gradTex.dispose();
  }

  const accentStripTex = (() => {
    const w = 1024;
    const h = 256;
    const cnv = document.createElement('canvas');
    cnv.width = w;
    cnv.height = h;
    const ctx = cnv.getContext('2d');
    const g = ctx.createLinearGradient(0, 0, w, 0);
    g.addColorStop(0, 'rgba(255,231,231,0.0)');
    g.addColorStop(0.25, 'rgba(255,231,231,0.28)');
    g.addColorStop(0.5, 'rgba(231,240,255,0.18)');
    g.addColorStop(0.75, 'rgba(255,231,231,0.28)');
    g.addColorStop(1, 'rgba(231,240,255,0.0)');
    ctx.fillStyle = g;
    ctx.fillRect(0, 0, w, h);
    const t = new THREE.CanvasTexture(cnv);
    t.colorSpace = THREE.SRGBColorSpace;
    t.wrapS = THREE.ClampToEdgeWrapping;
    t.wrapT = THREE.ClampToEdgeWrapping;
    t.needsUpdate = true;
    return t;
  })();

  const accentStripMat = new THREE.MeshStandardMaterial({ map: accentStripTex, transparent: true, metalness: 0.0, roughness: 0.95, emissive: 0xffffff, emissiveIntensity: 0.1 });
  accentStripMat.depthWrite = false;
  const leftAccent = new THREE.Mesh(new THREE.PlaneGeometry(depth, 1.4), accentStripMat);
  leftAccent.rotation.y = Math.PI / 2;
  leftAccent.position.set(-5.55, 2.4, (startZ + endZ) / 2);
  leftAccent.renderOrder = 1;
  scene.add(leftAccent);

  const rightAccent = leftAccent.clone();
  rightAccent.position.set(5.55, 2.4, (startZ + endZ) / 2);
  rightAccent.rotation.y = -Math.PI / 2;
  scene.add(rightAccent);

  {
    const w = 1024;
    const h = 256;
    const cnv = document.createElement('canvas');
    cnv.width = w;
    cnv.height = h;
    const ctx = cnv.getContext('2d');
    ctx.clearRect(0, 0, w, h);
    ctx.globalAlpha = 1;
    ctx.lineWidth = 8;
    ctx.strokeStyle = 'rgba(220,185,120,0.65)';
    ctx.shadowColor = 'rgba(220,185,120,0.35)';
    ctx.shadowBlur = 18;
    const drawWave = (y0, amp, freq, phase) => {
      ctx.beginPath();
      for (let x = 0; x <= w; x += 10) {
        const t = (x / w) * Math.PI * 2 * freq + phase;
        const y = y0 + Math.sin(t) * amp;
        if (x === 0) ctx.moveTo(x, y);
        else ctx.lineTo(x, y);
      }
      ctx.stroke();
    };
    drawWave(h * 0.38, 18, 2.2, 0.0);
    ctx.lineWidth = 6;
    ctx.strokeStyle = 'rgba(240,220,170,0.55)';
    ctx.shadowBlur = 14;
    drawWave(h * 0.62, 14, 2.0, 1.3);
    const goldWaveTex = new THREE.CanvasTexture(cnv);
    goldWaveTex.colorSpace = THREE.SRGBColorSpace;
    goldWaveTex.wrapS = THREE.RepeatWrapping;
    goldWaveTex.wrapT = THREE.ClampToEdgeWrapping;
    goldWaveTex.repeat.set(Math.max(1, depth / 26), 1);
    goldWaveTex.needsUpdate = true;

    const goldWaveMat = new THREE.MeshStandardMaterial({
      map: goldWaveTex,
      transparent: true,
      opacity: 0.95,
      metalness: 0.55,
      roughness: 0.25,
      emissive: 0xffe4a6,
      emissiveIntensity: 0.08
    });
    goldWaveMat.depthWrite = false;

    const waveL = new THREE.Mesh(new THREE.PlaneGeometry(depth, 0.7), goldWaveMat);
    waveL.rotation.y = Math.PI / 2;
    waveL.position.set(-5.57, 3.05, (startZ + endZ) / 2);
    waveL.renderOrder = 2;
    scene.add(waveL);

    const waveR = waveL.clone();
    waveR.rotation.y = -Math.PI / 2;
    waveR.position.set(5.57, 3.05, (startZ + endZ) / 2);
    scene.add(waveR);
  }

  {
    const ringMat = new THREE.MeshStandardMaterial({ color: 0xd7b87a, metalness: 0.8, roughness: 0.22, emissive: 0x2a1a08, emissiveIntensity: 0.06 });
    const ringGeo = new THREE.TorusGeometry(1.1, 0.035, 10, 64);
    const stemMat = new THREE.MeshStandardMaterial({ color: 0xffffff, metalness: 0.2, roughness: 0.55 });
    const stemGeo = new THREE.CylinderGeometry(0.018, 0.018, 0.28, 12);
    const chandelierStep = 11;
    const chandelierCount = Math.max(2, Math.ceil((Math.abs(endZ - startZ) + 8) / chandelierStep));
    for (let i = 0; i < chandelierCount; i++) {
      const z = startZ - i * chandelierStep;
      const ring = new THREE.Mesh(ringGeo, ringMat);
      ring.rotation.x = Math.PI / 2;
      ring.position.set(0, 4.42, z);
      ring.castShadow = true;
      ring.receiveShadow = true;
      scene.add(ring);

      const stem = new THREE.Mesh(stemGeo, stemMat);
      stem.position.set(0, 4.6, z);
      stem.castShadow = true;
      scene.add(stem);
    }
  }

  // WALL PATTERN
  function createWallPattern(width, height, depth, color1, color2, repeat = 10) {
    const canvas = document.createElement('canvas');
    canvas.width = 512;
    canvas.height = 512;
    const ctx = canvas.getContext('2d');

    // Draw vertical stripes
    const stripeWidth = canvas.width / repeat;
    for (let i = 0; i < repeat; i++) {
      ctx.fillStyle = i % 2 === 0 ? color1 : color2;
      ctx.fillRect(i * stripeWidth, 0, stripeWidth, canvas.height);
    }

    const texture = new THREE.CanvasTexture(canvas);
    texture.wrapS = THREE.RepeatWrapping;
    texture.wrapT = THREE.RepeatWrapping;
    texture.repeat.set(depth / 2, 1); // repeat along Z

    return new THREE.MeshStandardMaterial({
      map: texture,
      metalness: 0.1,
      roughness: 0.8
    });
  }

  // WALLS WITH PATTERN
  const patternedWallMat = createWallPattern(1, 1, depth, '#f3f0ee', '#ebe2e3', 12);

  const leftWallPattern = new THREE.Mesh(new THREE.BoxGeometry(0.35, 4.6, depth), patternedWallMat);
  leftWallPattern.position.set(-5.75, 2.3, (startZ + endZ) / 2);
  scene.add(leftWallPattern);

  const rightWallPattern = new THREE.Mesh(new THREE.BoxGeometry(0.35, 4.6, depth), patternedWallMat);
  rightWallPattern.position.set(5.75, 2.3, (startZ + endZ) / 2);
  scene.add(rightWallPattern);

  // CREATE DARKER LUXURY FLOOR TILES
  function createLuxuryFloorTiles(width, depth) {
    const tileSize = 1; // size of each tile in units
    const canvas = document.createElement('canvas');
    canvas.width = 1024;
    canvas.height = 1024;
    const ctx = canvas.getContext('2d');

    // Base luxury color (darker)
    ctx.fillStyle = '#d9c7b0'; // soft mocha
    ctx.fillRect(0, 0, canvas.width, canvas.height);

    // Golden marble veins (more visible)
    for (let i = 0; i < 80; i++) {
      const startX = Math.random() * canvas.width;
      const startY = Math.random() * canvas.height;
      const endX = startX + (Math.random() - 0.5) * 500;
      const endY = startY + (Math.random() - 0.5) * 500;
      ctx.strokeStyle = `rgba(200,150,100,${Math.random() * 0.6 + 0.4})`; // stronger contrast
      ctx.lineWidth = Math.random() * 3 + 2;
      ctx.beginPath();
      ctx.moveTo(startX, startY);
      ctx.lineTo(endX, endY);
      ctx.stroke();
    }

    // Draw tile lines (slightly darker)
    ctx.strokeStyle = 'rgba(140,120,100,0.45)';
    ctx.lineWidth = 3;
    const step = canvas.width / 6; // bigger tiles
    for (let i = 0; i <= canvas.width; i += step) {
      ctx.beginPath();
      ctx.moveTo(i, 0);
      ctx.lineTo(i, canvas.height);
      ctx.stroke();

      ctx.beginPath();
      ctx.moveTo(0, i);
      ctx.lineTo(canvas.width, i);
      ctx.stroke();
    }

    const texture = new THREE.CanvasTexture(canvas);
    texture.wrapS = THREE.RepeatWrapping;
    texture.wrapT = THREE.RepeatWrapping;
    texture.repeat.set(width / tileSize, depth / tileSize);
    texture.anisotropy = 8;
    texture.needsUpdate = true;

    const mat = new THREE.MeshStandardMaterial({
      map: texture,
      metalness: 0.3,
      roughness: 0.25, // polished luxury floor
      side: THREE.DoubleSide,
    });

    const floor = new THREE.Mesh(new THREE.PlaneGeometry(width, depth), mat);
    floor.rotation.x = -Math.PI / 2; // horizontal
    floor.position.set(0, 0.01, (startZ + endZ) / 2);
    floor.receiveShadow = true;
    return floor;
  }

  // Add tiles on top of the hallway floor
  const luxuryTileFloor = createLuxuryFloorTiles(12, depth);
  scene.add(luxuryTileFloor);

  // CREATE LUXURY CEILING TEXTURE
function createCeilingTexture(width, depth) {
  const canvas = document.createElement('canvas');
  canvas.width = 1024;
  canvas.height = 1024;
  const ctx = canvas.getContext('2d');

  // Base ceiling color (soft warm tone)
  ctx.fillStyle = '#f2ebe4';
  ctx.fillRect(0, 0, canvas.width, canvas.height);

  // Subtle decorative lines (luxury effect)
  ctx.strokeStyle = 'rgba(200,180,160,0.35)';
  ctx.lineWidth = 4;

  const step = canvas.width / 8;
  for (let i = 0; i <= canvas.width; i += step) {
    ctx.beginPath();
    ctx.moveTo(i, 0);
    ctx.lineTo(i, canvas.height);
    ctx.stroke();

    ctx.beginPath();
    ctx.moveTo(0, i);
    ctx.lineTo(canvas.width, i);
    ctx.stroke();
  }

  // Optional: subtle marble veins
  for (let i = 0; i < 50; i++) {
    const startX = Math.random() * canvas.width;
    const startY = Math.random() * canvas.height;
    const endX = startX + (Math.random() - 0.5) * 600;
    const endY = startY + (Math.random() - 0.5) * 600;
    ctx.strokeStyle = `rgba(210,190,170,${Math.random() * 0.4 + 0.2})`;
    ctx.lineWidth = Math.random() * 2 + 1;
    ctx.beginPath();
    ctx.moveTo(startX, startY);
    ctx.lineTo(endX, endY);
    ctx.stroke();
  }

  const texture = new THREE.CanvasTexture(canvas);
  texture.wrapS = THREE.RepeatWrapping;
  texture.wrapT = THREE.RepeatWrapping;
  texture.repeat.set(width / 6, depth / 6);
  texture.anisotropy = 8;
  texture.needsUpdate = true;

  const mat = new THREE.MeshStandardMaterial({
    map: texture,
    metalness: 0.15,
    roughness: 0.45,
    emissive: 0xffffff,
    emissiveIntensity: 0.05, // subtle glow
    side: THREE.DoubleSide,
  });

  return mat;
}

// Replace your ceiling material with texture
const ceilingMat = createCeilingTexture(12, depth);
ceiling.material = ceilingMat;
ceiling.material.needsUpdate = true;




  const archStep = 9;
  const archCount = Math.max(5, Math.ceil((Math.abs(endZ - startZ) + 14) / archStep));
  const archMat = new THREE.MeshStandardMaterial({ color: 0xf3f0ee, metalness: 0.0, roughness: 0.96 });
  const archInsetMat = new THREE.MeshStandardMaterial({ color: 0xebe2e3, metalness: 0.0, roughness: 0.98 });
  const columnShaftMat = new THREE.MeshStandardMaterial({ color: 0xf8f6f4, metalness: 0.0, roughness: 0.78 });
  const columnCapMat = new THREE.MeshStandardMaterial({ color: 0xffffff, metalness: 0.0, roughness: 0.72 });

  const archR = 1.38;
  const archTopGeo = new THREE.TorusGeometry(archR, 0.2, 14, 56, Math.PI);
  const insetTopGeo = new THREE.TorusGeometry(archR - 0.16, 0.11, 10, 48, Math.PI);
  const insetPlaneGeo = new THREE.PlaneGeometry(2.85, 3.55);

  const makeColumn = (x, z, wallSign) => {
    const g = new THREE.Group();
    const base = new THREE.Mesh(new THREE.CylinderGeometry(0.26, 0.32, 0.2, 20), columnCapMat);
    base.position.set(x, 0.1, z);
    base.castShadow = true;
    base.receiveShadow = true;
    g.add(base);
    const plinth = new THREE.Mesh(new THREE.BoxGeometry(0.76, 0.14, 0.58), columnCapMat);
    plinth.position.set(x, 0.24, z);
    plinth.castShadow = true;
    plinth.receiveShadow = true;
    g.add(plinth);
    const shaft = new THREE.Mesh(new THREE.CylinderGeometry(0.18, 0.2, 3.15, 24), columnShaftMat);
    shaft.position.set(x, 1.86, z);
    shaft.castShadow = true;
    shaft.receiveShadow = true;
    g.add(shaft);
    const cap = new THREE.Mesh(new THREE.CylinderGeometry(0.25, 0.22, 0.22, 20), columnCapMat);
    cap.position.set(x, 3.52, z);
    cap.castShadow = true;
    cap.receiveShadow = true;
    g.add(cap);
    const crown = new THREE.Mesh(new THREE.BoxGeometry(0.86, 0.18, 0.64), columnCapMat);
    crown.position.set(x, 3.68, z);
    crown.castShadow = true;
    crown.receiveShadow = true;
    g.add(crown);
    g.position.x += 0.0;
    if (wallSign < 0) g.position.x += 0.0;
    scene.add(g);
  };

  for (let i = 0; i < archCount; i++) {
    const z = startZ - i * archStep;

    {
      const xWall = -5.58;
      const zL = z - archR;
      const zR = z + archR;
      const wallZ = z;

      makeColumn(xWall, zL, -1);
      makeColumn(xWall, zR, -1);

      const top = new THREE.Mesh(archTopGeo, archMat);
      top.rotation.y = Math.PI / 2;
      top.rotation.z = Math.PI;
      top.position.set(xWall, 4.05, wallZ);
      top.castShadow = true;
      top.receiveShadow = true;
      scene.add(top);

      const insetTop = new THREE.Mesh(insetTopGeo, archInsetMat);
      insetTop.rotation.y = Math.PI / 2;
      insetTop.rotation.z = Math.PI;
      insetTop.position.set(xWall + 0.12, 3.98, wallZ);
      insetTop.castShadow = true;
      insetTop.receiveShadow = true;
      scene.add(insetTop);

      const inset = new THREE.Mesh(insetPlaneGeo, archInsetMat);
      inset.rotation.y = Math.PI / 2;
      inset.position.set(xWall + 0.12, 2.05, wallZ);
      inset.castShadow = true;
      inset.receiveShadow = true;
      scene.add(inset);
    }

    {
      const xWall = 5.58;
      const zL = z - archR;
      const zR = z + archR;
      const wallZ = z;

      makeColumn(xWall, zL, 1);
      makeColumn(xWall, zR, 1);

      const top = new THREE.Mesh(archTopGeo, archMat);
      top.rotation.y = -Math.PI / 2;
      top.rotation.z = Math.PI;
      top.position.set(xWall, 4.05, wallZ);
      top.castShadow = true;
      top.receiveShadow = true;
      scene.add(top);

      const insetTop = new THREE.Mesh(insetTopGeo, archInsetMat);
      insetTop.rotation.y = -Math.PI / 2;
      insetTop.rotation.z = Math.PI;
      insetTop.position.set(xWall - 0.12, 3.98, wallZ);
      insetTop.castShadow = true;
      insetTop.receiveShadow = true;
      scene.add(insetTop);

      const inset = new THREE.Mesh(insetPlaneGeo, archInsetMat);
      inset.rotation.y = -Math.PI / 2;
      inset.position.set(xWall - 0.12, 2.05, wallZ);
      inset.castShadow = true;
      inset.receiveShadow = true;
      scene.add(inset);
    }
  }

  {
    const count = 520;
    const positions = new Float32Array(count * 3);
    const speeds = new Float32Array(count * 3);
    for (let i = 0; i < count; i++) {
      const i3 = i * 3;
      positions[i3 + 0] = (Math.random() - 0.5) * 8.5;
      positions[i3 + 1] = 0.6 + Math.random() * 3.6;
      positions[i3 + 2] = endZ + Math.random() * (startZ - endZ);

      speeds[i3 + 0] = (Math.random() - 0.5) * 0.06;
      speeds[i3 + 1] = -0.02 - Math.random() * 0.03;
      speeds[i3 + 2] = (Math.random() - 0.5) * 0.04;
    }

    const geo = new THREE.BufferGeometry();
    geo.setAttribute('position', new THREE.BufferAttribute(positions, 3));
    const mat = new THREE.PointsMaterial({
      color: 0xffffff,
      size: 0.075,
      transparent: true,
      opacity: 0.32,
      depthWrite: false,
      depthTest: false
    });
    hallwayParticles = new THREE.Points(geo, mat);
    hallwayParticlesMeta = { speeds, startZ, endZ };
    hallwayParticles.renderOrder = 9;
    scene.add(hallwayParticles);
  }

  // Store shop positions and card references for search navigation
  state.shopPositions = {};
  state.shopCards = {}; // Store card groups for position lookup
  const sideOffset = 0.8; // how far from center
  for (let i = 0; i < slice.length; i++) {
    const supplier = slice[i];
    const z = firstCardZ - i * cardSpacing;
    const card = createShopCard({ supplier, index: i, z });

    // Move cards alternately to left/right walls
    if (i % 2 === 0) card.group.position.x = -sideOffset; // left
    else card.group.position.x = sideOffset; // right

    scene.add(card.group);

    clickable.push(card.hit);
    const supplierId = supplier.supplier_id || (state.floorIndex * state.shopsPerFloor + i + 1);
    state.shopPositions[supplierId] = {
      z,
      name: supplier.company_name || `Shop ${supplierId}`,
      floorIndex: state.floorIndex,
      cardGroup: card.group,
      hitObject: card.hit
    };
    state.shopCards[supplierId] = card;
  }

  const hasNextFloor = (state.floorIndex + 1) * state.shopsPerFloor < total;
  const hasPrevFloor = state.floorIndex > 0;

  // Position both buttons at the end, side by side
  const buttonSpacing = 5;
  const buttonZ = endZ + 3;

  if (hasNextFloor) {
    const nextTex = makeTextTexture('Next Floor', {
      width: 1024,
      height: 256,
      bg: 'rgba(255,255,255,1.0)',
      color: '#0b1020',
      font: '600 78px Inter, Arial'
    });
    const nextMat = new THREE.MeshStandardMaterial({ map: nextTex, metalness: 0.0, roughness: 0.92 });
    const nextPanel = new THREE.Mesh(new THREE.PlaneGeometry(4.2, 1.1), nextMat);
    nextPanel.position.set(hasPrevFloor ? -buttonSpacing / 2 : 0, 2.2, buttonZ);
    nextPanel.userData = { kind: 'nextFloor' };
    scene.add(nextPanel);
    clickable.push(nextPanel);
  }

  if (hasPrevFloor) {
    const prevTex = makeTextTexture('Prev Floor', {
      width: 1024,
      height: 256,
      bg: 'rgba(255,255,255,1.0)',
      color: '#0b1020',
      font: '600 78px Inter, Arial'
    });
    const prevMat = new THREE.MeshStandardMaterial({ map: prevTex, metalness: 0.0, roughness: 0.92 });
    const prevPanel = new THREE.Mesh(new THREE.PlaneGeometry(4.2, 1.1), prevMat);
    prevPanel.position.set(hasNextFloor ? buttonSpacing / 2 : 0, 2.2, buttonZ);
    prevPanel.userData = { kind: 'prevFloor' };
    scene.add(prevPanel);
    clickable.push(prevPanel);
  }

  state.hallwayProgress = 0;

  // Setup search functionality
  setupSearchAndFloorSelector();

  const cameraStartZ = 6;
  const cameraEndZ = endZ + 6;
  state.hallwayMaxProgress = Math.max(0, cameraStartZ - cameraEndZ);

  camera.position.set(0, 1.72, 6);
  state.cameraTargetPos.set(0, 1.72, 6);
  state.cameraLookAt.set(0, 1.6, 0);
  state.cameraTargetLookAt.set(0, 1.6, -10);
}

function createShopInterior(shopId) {
  clearSceneGeometry();
  setHud(`Shop ${shopId}`, 'Click Back to return to hallway');
  btnBack.hidden = false;

  // Hide search container when in shop - make completely invisible
  if (searchContainer) {
    searchContainer.hidden = true;
    searchContainer.style.opacity = '0';
    searchContainer.style.visibility = 'hidden';
  }

  scene.fog = null;

  const room = new THREE.Mesh(
    new THREE.BoxGeometry(12, 6, 12),
    new THREE.MeshStandardMaterial({
      color: 0x0c1020,
      metalness: 0.05,
      roughness: 0.95,
      side: THREE.BackSide
    })
  );
  room.position.set(0, 3.0, -6);
  scene.add(room);

  const floor = createFloor(12, 12);
  floor.position.set(0, 0.0, -6);

  const display = new THREE.Mesh(
    new THREE.BoxGeometry(6, 1.2, 1.6),
    new THREE.MeshStandardMaterial({ color: 0x182447, metalness: 0.1, roughness: 0.65 })
  );
  display.position.set(0, 0.6, -7.6);
  scene.add(display);

  const logo = new THREE.Mesh(
    new THREE.SphereGeometry(0.65, 32, 32),
    new THREE.MeshStandardMaterial({
      color: 0x2d6cff,
      emissive: 0x2d6cff,
      emissiveIntensity: 1.4,
      metalness: 0.0,
      roughness: 0.35
    })
  );
  logo.position.set(0, 2.2, -9.3);
  scene.add(logo);

  camera.position.set(0, 1.7, 1.2);
  state.cameraTargetPos.set(0, 1.7, 1.2);
  state.cameraLookAt.set(0, 2.1, -7.0);
  state.cameraTargetLookAt.set(0, 2.1, -7.0);
}

async function transitionTo(nextMode, opts = {}) {
  if (state.transitioning) return;
  state.transitioning = true;

  setFade(true);
  await wait(620);

  state.mode = nextMode;
  if (nextMode === 'outside') createOutside();
  if (nextMode === 'hallway') {
    if (typeof opts.floorIndex === 'number') state.floorIndex = opts.floorIndex;
    createHallway();
  }
  if (nextMode === 'shop') createShopInterior(opts.shopId);

  await wait(60);
  setFade(false);
  await wait(650);

  state.transitioning = false;
}

function updateCamera(dt) {
  const lerpT = 1 - Math.pow(0.001, dt);
  camera.position.lerp(state.cameraTargetPos, lerpT);
  state.cameraLookAt.lerp(state.cameraTargetLookAt, lerpT);
  camera.lookAt(state.cameraLookAt);
}

function onResize() {
  const w = canvas.clientWidth;
  const h = canvas.clientHeight;
  renderer.setSize(w, h, false);
  camera.aspect = w / h;
  camera.updateProjectionMatrix();
}

window.addEventListener('resize', onResize);

function setPointerFromEvent(e) {
  const rect = canvas.getBoundingClientRect();
  const x = ((e.clientX - rect.left) / rect.width) * 2 - 1;
  const y = -(((e.clientY - rect.top) / rect.height) * 2 - 1);
  pointerNdc.set(x, y);
}

canvas.addEventListener('pointerdown', async (e) => {
  if (state.transitioning) return;

  setPointerFromEvent(e);
  raycaster.setFromCamera(pointerNdc, camera);
  const hits = raycaster.intersectObjects(clickable, false);
  if (!hits.length) return;

  const obj = hits[0].object;
  const kind = obj.userData && obj.userData.kind;

  if (state.mode === 'outside' && kind === 'mallDoor') {
    if (overlayEl) overlayEl.hidden = true;
    state.cameraTargetPos.set(0, 1.65, 1.6);
    state.cameraTargetLookAt.set(0, 2.2, -2.2);
    setHud('Outside', 'Entering...');
    await wait(450);
    await transitionTo('hallway');
    return;
  }

  if (state.mode === 'hallway') {
    if (kind === 'nextFloor') {
      await transitionTo('hallway', { floorIndex: state.floorIndex + 1 });
      return;
    }

    if (kind === 'prevFloor') {
      await transitionTo('hallway', { floorIndex: state.floorIndex - 1 });
      return;
    }

    if (kind === 'shopCard') {
      const shopId = obj.userData.shopId;
      setHud('Mall Hallway', `Entering shop ${shopId}...`);

      state.transitioning = true;

      const p = new THREE.Vector3();
      obj.getWorldPosition(p);

      const dirX = Math.sign(p.x) || 1;
      state.cameraTargetPos.set(dirX * 1.25, 1.7, p.z + 2.2);
      state.cameraTargetLookAt.set(p.x, 2.15, p.z);

      await wait(720);
      setFade(true);
      await wait(620);
      window.location.href = `../shop/?supplier_id=${encodeURIComponent(shopId)}`;
      return;
    }
  }
});

window.addEventListener(
  'wheel',
  (e) => {
    if (state.transitioning) return;
    if (state.mode !== 'hallway') return;

    const delta = Math.max(-1, Math.min(1, e.deltaY / 100));
    state.hallwayProgress += delta * 2.6;
    state.hallwayProgress = Math.max(0, Math.min(state.hallwayMaxProgress, state.hallwayProgress));

    const z = 6 - state.hallwayProgress;
    state.cameraTargetPos.z = z;

    state.cameraTargetLookAt.set(0, 1.6, z - 10);
  },
  { passive: true }
);

btnBack.addEventListener('click', async () => {
  if (state.transitioning) return;

  if (state.mode === 'hallway') {
    await transitionTo('outside');
    return;
  }

  if (state.mode === 'shop') {
    await transitionTo('hallway');
    return;
  }
});

if (btnEnter) {
  btnEnter.addEventListener('click', async () => {
    if (state.transitioning) return;
    if (overlayEl) overlayEl.hidden = true;
    state.cameraTargetPos.set(0, 1.65, 1.6);
    state.cameraTargetLookAt.set(0, 2.2, -2.2);
    setHud('Outside', 'Entering...');
    await wait(450);
    await transitionTo('hallway');
  });
}

function animate() {
  requestAnimationFrame(animate);
  const dt = Math.min(clock.getDelta(), 0.05);
  const time = clock.getElapsedTime();

  // --- Animate dreamy floating elements ---
  if (scene.userData.dreamyOrbs) {
    scene.userData.dreamyOrbs.forEach((orb) => {
      orb.position.y = orb.userData.baseY + Math.sin(time * orb.userData.speed + orb.userData.phase) * 0.5;
      orb.rotation.x += orb.userData.rotationSpeed;
      orb.rotation.y += orb.userData.rotationSpeed * 0.7;
    });
  }

  if (scene.userData.dreamyShapes) {
    scene.userData.dreamyShapes.forEach((shape) => {
      if (shape.userData.baseY !== undefined) {
        // Floating geometric shapes
        shape.position.y = shape.userData.baseY + Math.sin(time * shape.userData.speed + shape.userData.phase) * 0.4;
        shape.rotation.x += shape.userData.rotationSpeed;
        shape.rotation.y += shape.userData.rotationSpeed * 0.8;
        shape.rotation.z += shape.userData.rotationSpeed * 0.5;
      } else if (shape.userData.baseRotation !== undefined) {
        // Light rays - gentle rotation
        shape.rotation.y = shape.userData.baseRotation + Math.sin(time * shape.userData.speed) * 0.1;
        shape.material.opacity = 0.2 + Math.sin(time * shape.userData.speed * 2) * 0.1;
      }
    });
  }

  // --- Animate hallway particles ---
  if (hallwayParticles && hallwayParticlesMeta) {
    const pos = hallwayParticles.geometry.attributes.position;
    const arr = pos.array;
    const { speeds, startZ, endZ } = hallwayParticlesMeta;
    for (let i = 0; i < arr.length; i += 3) {
      arr[i + 0] += speeds[i + 0] * dt;
      arr[i + 1] += speeds[i + 1] * dt;
      arr[i + 2] += speeds[i + 2] * dt;

      if (arr[i + 0] > 4.6) arr[i + 0] = -4.6;
      if (arr[i + 0] < -4.6) arr[i + 0] = 4.6;
      if (arr[i + 1] > 4.2) arr[i + 1] = 0.6;
      if (arr[i + 1] < 0.4) arr[i + 1] = 4.0;
      if (arr[i + 2] > startZ) arr[i + 2] = endZ;
      if (arr[i + 2] < endZ) arr[i + 2] = startZ;
    }
    pos.needsUpdate = true;
  }

  // --- Normal camera updates ---
  updateCamera(dt);

  renderer.render(scene, camera);
}

// Store search timeout and prevent duplicate listeners
let searchTimeout = null;
let searchListenerAttached = false;
let floorListenerAttached = false;

function setupSearchAndFloorSelector() {
  if (!searchBar || !floorSelector) return;

  // Search functionality - only attach once
  if (!searchListenerAttached) {
    searchBar.addEventListener('input', (e) => {
      if (searchTimeout) clearTimeout(searchTimeout);
      const query = e.target.value.trim().toLowerCase();

      if (!query) return;

      searchTimeout = setTimeout(() => {
        // Get all suppliers (across all floors)
        const allSuppliers = state.suppliers.length
          ? state.suppliers
          : Array.from({ length: Math.ceil(state.shopsPerFloor * 3) }).map((_, idx) => ({
            supplier_id: idx + 1,
            company_name: `Shop ${idx + 1}`
          }));

        // Find matching shop
        const found = allSuppliers.find(supplier => {
          const name = (supplier.company_name || `Shop ${supplier.supplier_id}`).toLowerCase();
          return name.includes(query);
        });

        if (found) {
          // Calculate which floor this shop is on
          const shopId = found.supplier_id || 1;
          const shopFloorIndex = Math.floor((shopId - 1) / state.shopsPerFloor);

          // Switch to the correct floor if needed
          if (shopFloorIndex !== state.floorIndex) {
            state.floorIndex = shopFloorIndex;
            if (floorSelector) floorSelector.value = shopFloorIndex;
            transitionTo('hallway', { floorIndex: shopFloorIndex }).then(() => {
              // Wait a bit for the hallway to be created, then navigate
              setTimeout(() => {
                navigateToShop(shopId);
              }, 1000);
            });
          } else {
            // Navigate to the shop on current floor
            navigateToShop(shopId);
          }
        }
      }, 300);
    });
    searchListenerAttached = true;
  }

  // Floor selector functionality - only attach once
  if (!floorListenerAttached) {
    floorSelector.addEventListener('change', (e) => {
      const selectedFloor = parseInt(e.target.value);
      if (selectedFloor !== state.floorIndex && !isNaN(selectedFloor)) {
        transitionTo('hallway', { floorIndex: selectedFloor });
      }
    });
    floorListenerAttached = true;
  }
}

function navigateToShop(shopId) {
  // Wait a moment for shop positions to be set
  setTimeout(() => {
    if (!state.shopPositions || !state.shopPositions[shopId]) {
      // Try to calculate position if not stored
      const shopFloorIndex = Math.floor((shopId - 1) / state.shopsPerFloor);
      const shopIndexOnFloor = (shopId - 1) % state.shopsPerFloor;

      if (shopFloorIndex === state.floorIndex) {
        const cardSpacing = 11;
        const firstCardZ = -6;
        const z = firstCardZ - shopIndexOnFloor * cardSpacing;
        const side = shopIndexOnFloor % 2 === 0 ? -1 : 1;
        const x = side * 2.25;

        // Navigate camera to the front of the shop (same as clicking)
        const dirX = Math.sign(x) || 1;
        state.cameraTargetPos.set(dirX * 1.25, 1.7, z + 2.2);
        state.cameraTargetLookAt.set(x, 2.15, z);
        state.hallwayProgress = Math.max(0, Math.min(state.hallwayMaxProgress, -z + 10));
      }
      return;
    }

    const shopData = state.shopPositions[shopId];

    // Get the shop card's world position (same as clicking)
    if (shopData.hitObject) {
      const p = new THREE.Vector3();
      shopData.hitObject.getWorldPosition(p);

      const dirX = Math.sign(p.x) || 1;
      state.cameraTargetPos.set(dirX * 1.25, 1.7, p.z + 2.2);
      state.cameraTargetLookAt.set(p.x, 2.15, p.z);
      state.hallwayProgress = Math.max(0, Math.min(state.hallwayMaxProgress, -p.z + 10));
    } else {
      // Fallback to calculated position
      const dirX = shopData.z < -10 ? -1 : 1; // Determine side based on position
      state.cameraTargetPos.set(dirX * 1.25, 1.7, shopData.z + 2.2);
      state.cameraTargetLookAt.set(dirX * 2.25, 2.15, shopData.z);
      state.hallwayProgress = Math.max(0, Math.min(state.hallwayMaxProgress, -shopData.z + 10));
    }
  }, 200);
}

onResize();
await loadSuppliers();
createOutside();
setupSearchAndFloorSelector(); // Initialize search and floor selector
animate();
