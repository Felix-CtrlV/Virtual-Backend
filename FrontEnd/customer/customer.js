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
  setHud('Mall Exterior', 'Click the Entrance Doors to Enter');
  btnBack.hidden = true;

  // Hide search UI
  if (searchContainer) {
    searchContainer.hidden = true;
    searchContainer.style.opacity = '0';
    searchContainer.style.visibility = 'hidden';
  }
  if (overlayEl) overlayEl.hidden = false;

  // --- 1. TEXTURE GENERATORS ---

  // A. Pavement
  function createPavementTexture() {
    const canvas = document.createElement('canvas');
    canvas.width = 512; canvas.height = 512;
    const ctx = canvas.getContext('2d');
    ctx.fillStyle = '#b0b0b0'; ctx.fillRect(0, 0, 512, 512);
    ctx.strokeStyle = '#999'; ctx.lineWidth = 4;
    const step = 64;
    for (let y = 0; y < 512; y += step) {
      for (let x = 0; x < 512; x += step) {
        ctx.fillStyle = (Math.random() > 0.5) ? '#bfbfbf' : '#aaaaaa';
        ctx.fillRect(x + 2, y + 2, step - 4, step - 4);
      }
    }
    const tex = new THREE.CanvasTexture(canvas);
    tex.wrapS = THREE.RepeatWrapping; tex.wrapT = THREE.RepeatWrapping;
    tex.repeat.set(2, 8);
    return tex;
  }

  // B. Modern Door Texture (Lighter & Higher Contrast)
  function createModernDoorTexture() {
    const canvas = document.createElement('canvas');
    canvas.width = 512; canvas.height = 1024;
    const ctx = canvas.getContext('2d');

    // 1. Lighter Glass Base (so it pops against black frame)
    const grad = ctx.createLinearGradient(0, 0, 512, 1024);
    grad.addColorStop(0, '#4a4a4a'); // Lighter gray top
    grad.addColorStop(1, '#2a2a2a'); // Darker gray bottom
    ctx.fillStyle = grad;
    ctx.fillRect(0, 0, 512, 1024);

    // 2. Strong Reflection Streak
    ctx.fillStyle = 'rgba(255,255,255,0.1)';
    ctx.beginPath();
    ctx.moveTo(0, 0); ctx.lineTo(300, 0); ctx.lineTo(0, 600);
    ctx.fill();

    // 3. Inner Metal Frame Border
    ctx.strokeStyle = '#111';
    ctx.lineWidth = 20;
    ctx.strokeRect(10, 10, 492, 1004);

    // 4. Handle (Chrome style)
    const handleX = 40, handleY = 400, handleH = 300;
    const hGrad = ctx.createLinearGradient(handleX, 0, handleX + 25, 0);
    hGrad.addColorStop(0, '#555');
    hGrad.addColorStop(0.5, '#fff'); // Bright highlight
    hGrad.addColorStop(1, '#555');
    ctx.fillStyle = hGrad;
    ctx.fillRect(handleX, handleY, 25, handleH);

    // 5. PULL Text
    ctx.save();
    ctx.translate(handleX + 60, handleY + 160);
    ctx.rotate(-Math.PI / 2);
    ctx.fillStyle = 'rgba(255,255,255,0.6)';
    ctx.font = '700 34px sans-serif';
    ctx.fillText("PULL", 0, 0);
    ctx.restore();

    const tex = new THREE.CanvasTexture(canvas);
    tex.colorSpace = THREE.SRGBColorSpace;
    return tex;
  }

  // C. Clouds
  function createCloudTexture() {
    const canvas = document.createElement('canvas');
    canvas.width = 128; canvas.height = 128;
    const ctx = canvas.getContext('2d');
    const grad = ctx.createRadialGradient(64, 64, 0, 64, 64, 64);
    grad.addColorStop(0, 'rgba(255,255,255,0.9)');
    grad.addColorStop(1, 'rgba(255,255,255,0.0)');
    ctx.fillStyle = grad; ctx.fillRect(0, 0, 128, 128);
    return new THREE.CanvasTexture(canvas);
  }

  // --- 2. LIGHTING ---
  scene.background = new THREE.Color('#87CEEB');
  scene.fog = new THREE.FogExp2(0x87CEEB, 0.012);
  ambient.intensity = 0.7;
  keyLight.intensity = 1.5; keyLight.position.set(30, 60, 40);
  fillLight.intensity = 0.5;

  // --- 3. MATERIALS ---
  const pavementTex = createPavementTexture();
  const pavementMat = new THREE.MeshStandardMaterial({ map: pavementTex, roughness: 0.8 });
  const glassMat = new THREE.MeshPhysicalMaterial({ color: 0x88ccee, metalness: 0.9, roughness: 0.05, transmission: 0.2, opacity: 0.7, transparent: true, side: THREE.DoubleSide });
  const frameMat = new THREE.MeshStandardMaterial({ color: 0x111111, roughness: 0.4, metalness: 0.8 }); // Dark Metal
  const slabMat = new THREE.MeshStandardMaterial({ color: 0xfdfdfd, roughness: 0.2 });
  const curbMat = new THREE.MeshStandardMaterial({ color: 0xcccccc, roughness: 0.9 });

  // --- 4. CLOUDS ---
  const cloudTex = createCloudTexture();
  const cloudMat = new THREE.SpriteMaterial({ map: cloudTex, transparent: true, opacity: 0.8, color: 0xffffff });
  const cloudGroup = new THREE.Group();
  for (let i = 0; i < 20; i++) {
    const sprite = new THREE.Sprite(cloudMat);
    sprite.position.set((Math.random() - 0.5) * 180, 15 + Math.random() * 20, (Math.random() - 0.5) * 60 - 20);
    sprite.scale.set(30 + Math.random() * 20, 15 + Math.random() * 10, 1);
    cloudGroup.add(sprite);
  }
  scene.add(cloudGroup);
  scene.userData.dreamyShapes = cloudGroup.children.map(c => ({
    ...c, userData: { speed: 0.02, baseY: c.position.y, phase: Math.random() * Math.PI }
  }));

  // --- 5. GROUND & WALKWAY ---
  const ground = new THREE.Mesh(new THREE.PlaneGeometry(250, 250), new THREE.MeshStandardMaterial({ color: 0x3a5a3a }));
  ground.rotation.x = -Math.PI / 2; ground.position.y = -0.1;
  scene.add(ground);

  const walkway = new THREE.Mesh(new THREE.PlaneGeometry(14, 70), pavementMat);
  walkway.rotation.x = -Math.PI / 2; walkway.position.set(0, 0.02, 10);
  walkway.receiveShadow = true;
  scene.add(walkway);

  const curbGeo = new THREE.BoxGeometry(0.6, 0.15, 70);
  const leftCurb = new THREE.Mesh(curbGeo, curbMat);
  leftCurb.position.set(-7.3, 0.075, 10); leftCurb.receiveShadow = true; scene.add(leftCurb);

  const rightCurb = new THREE.Mesh(curbGeo, curbMat);
  rightCurb.position.set(7.3, 0.075, 10); rightCurb.receiveShadow = true; scene.add(rightCurb);

  // --- 6. 3D GRASS ---
  function createDenseGrass(count, centerX, centerZ, width, depth) {
    const bladeGeo = new THREE.ConeGeometry(0.12, 0.6, 2);
    bladeGeo.translate(0, 0.3, 0);
    const bladeMat = new THREE.MeshStandardMaterial({ color: 0x4caf50, roughness: 0.9, side: THREE.DoubleSide });
    const mesh = new THREE.InstancedMesh(bladeGeo, bladeMat, count);
    const dummy = new THREE.Object3D();
    const _color = new THREE.Color();
    for (let i = 0; i < count; i++) {
      dummy.position.set(centerX + (Math.random() - 0.5) * width, 0, centerZ + (Math.random() - 0.5) * depth);
      dummy.rotation.set((Math.random() - 0.5) * 0.3, Math.random() * Math.PI, (Math.random() - 0.5) * 0.3);
      dummy.scale.setScalar(0.8 + Math.random() * 0.7);
      dummy.updateMatrix();
      mesh.setMatrixAt(i, dummy.matrix);
      _color.setHex(Math.random() > 0.8 ? 0x3e8e41 : 0x5cb85c);
      mesh.setColorAt(i, _color);
    }
    mesh.instanceMatrix.needsUpdate = true; mesh.instanceColor.needsUpdate = true; mesh.receiveShadow = true;
    scene.add(mesh);
  }
  createDenseGrass(8000, -28, 10, 35, 60);
  createDenseGrass(8000, 28, 10, 35, 60);

  // --- 7. TREES ---
  function createTree(x, z) {
    const g = new THREE.Group(); g.position.set(x, 0, z);
    const t = new THREE.Mesh(new THREE.CylinderGeometry(0.2, 0.35, 1.5, 6), new THREE.MeshStandardMaterial({ color: 0x4a3c31 }));
    t.position.y = 0.75; g.add(t);
    const lMat = new THREE.MeshStandardMaterial({ color: 0x2d5a27 });
    const lGeo = new THREE.DodecahedronGeometry(1.3);
    const l1 = new THREE.Mesh(lGeo, lMat); l1.position.y = 2.2; g.add(l1);
    const l2 = l1.clone(); l2.position.set(0.7, 2.5, 0); l2.scale.setScalar(0.7); g.add(l2);
    const l3 = l1.clone(); l3.position.set(-0.7, 2.3, 0.4); l3.scale.setScalar(0.8); g.add(l3);
    scene.add(g);
  }
  for (let z = -5; z < 35; z += 12) { createTree(-10, z); createTree(10, z); }

  // --- 8. BUILDING ---
  function getBuildingLoc(x) {
    const freq = 0.08, amp = 6.0, baseZ = -18.0;
    const z = baseZ + Math.cos(x * freq) * amp;
    const rotY = Math.atan(-freq * amp * Math.sin(x * freq));
    return { z, rotY };
  }
  const bWidth = 90, bHeight = 24, segs = 120;
  const wGeo = new THREE.PlaneGeometry(bWidth, bHeight, segs, 1);
  const pos = wGeo.attributes.position;
  for (let i = 0; i < pos.count; i++) {
    const { z } = getBuildingLoc(pos.getX(i));
    pos.setZ(i, z);
  }
  wGeo.computeVertexNormals();
  const b = new THREE.Mesh(wGeo, glassMat); b.position.y = bHeight / 2; b.receiveShadow = true; scene.add(b);

  function addRibbon(y, h) {
    const g = new THREE.PlaneGeometry(bWidth, h, segs, 1);
    const p = g.attributes.position;
    for (let i = 0; i < p.count; i++) {
      const x = p.getX(i); const l = getBuildingLoc(x);
      const nx = -Math.sin(l.rotY), nz = Math.cos(l.rotY);
      p.setXYZ(i, x + nx * 0.3, p.getY(i), l.z + nz * 0.3);
    }
    g.computeVertexNormals();
    const m = new THREE.Mesh(g, slabMat); m.position.y = y; scene.add(m);
  }
  [0.5, 8.0, 16.0, 23.5].forEach(y => addRibbon(y, y === 23.5 ? 1.2 : 0.9));

  const mulGeo = new THREE.BoxGeometry(0.15, bHeight, 0.3);
  for (let i = 0; i <= 36; i++) {
    const x = -bWidth / 2 + (i / 36) * bWidth; const l = getBuildingLoc(x);
    const m = new THREE.Mesh(mulGeo, frameMat);
    m.position.set(x, bHeight / 2, l.z); m.rotation.y = l.rotY; scene.add(m);
  }

  // --- 9. ENTRANCE & DOORS (FIXED VISIBILITY) ---
  const entLoc = getBuildingLoc(0);
  const dGrp = new THREE.Group();
  dGrp.position.set(0, 0, entLoc.z); dGrp.rotation.y = entLoc.rotY;
  scene.add(dGrp);

  // Canopy
  const canopy = new THREE.Mesh(new THREE.BoxGeometry(14, 0.5, 10), slabMat);
  canopy.position.set(0, 6.5, 5); dGrp.add(canopy);

  // Pillars
  const cG = new THREE.CylinderGeometry(0.25, 0.25, 6.5);
  const c1 = new THREE.Mesh(cG, frameMat); c1.position.set(-6, 3.25, 9); dGrp.add(c1);
  const c2 = c1.clone(); c2.position.set(6, 3.25, 9); dGrp.add(c2);

  // FRAME (Black Box) - Pushed BACK to Z = -0.5
  // This ensures it is BEHIND the doors
  const df = new THREE.Mesh(new THREE.BoxGeometry(8, 5, 0.5), frameMat);
  df.position.set(0, 2.5, 0.5);
  dGrp.add(df);

  // DOORS - Pulled FORWARD to Z = 0.0
  const dTex = createModernDoorTexture();
  const dMat = new THREE.MeshStandardMaterial({
    map: dTex,
    roughness: 0.2,
    metalness: 0.6,
    envMapIntensity: 1.0
  });
  const dGeo = new THREE.BoxGeometry(3.5, 4.6, 0.15); // Thin doors

  const ld = new THREE.Mesh(dGeo, dMat);
  ld.position.set(1.85, 2.5, 1);
  ld.userData = { kind: 'mallDoor' };
  dGrp.add(ld);
  clickable.push(ld);

  const rd = ld.clone();
  rd.position.set(-1.85, 2.5, 1);

  // 🔥 MIRROR IT
  rd.scale.x = -1;

  rd.userData = { kind: 'mallDoor' };
  dGrp.add(rd);
  clickable.push(rd);


  // Sign
  const signTex = makeTextTexture('MALLTIVERSE', { width: 1024, height: 256, bg: 'rgba(0,0,0,0)', color: '#111', font: '900 120px Inter, sans-serif' });
  const sign = new THREE.Mesh(new THREE.PlaneGeometry(12, 3), new THREE.MeshStandardMaterial({ map: signTex, transparent: true }));
  sign.position.set(0, 8.0, 5); dGrp.add(sign);

  // --- 10. LIGHT POLES (NEW: MODERN DESIGN) ---
  function createLightPole(x, z, rotY) {
    const g = new THREE.Group();
    g.position.set(x, 0, z);
    g.rotation.y = rotY;

    // Pole
    const pole = new THREE.Mesh(
      new THREE.CylinderGeometry(0.1, 0.2, 6, 16),
      frameMat
    );
    pole.position.y = 3;
    g.add(pole);

    // 🔑 ARM PIVOT (hinge point)
    const armPivot = new THREE.Group();
    armPivot.position.set(0, 6, 0); // top of pole
    g.add(armPivot);

    // Arm (extends forward)
    const arm = new THREE.Mesh(
      new THREE.BoxGeometry(1.5, 0.12, 0.12),
      frameMat
    );
    arm.position.x = 0.75; // half its length
    armPivot.add(arm);

    // Head
    const head = new THREE.Mesh(
      new THREE.BoxGeometry(0.8, 0.15, 0.4),
      frameMat
    );
    head.position.set(1.6, -0.05, 0);
    armPivot.add(head);

    // Emissive bulb
    const bulb = new THREE.Mesh(
      new THREE.PlaneGeometry(0.6, 0.3),
      new THREE.MeshStandardMaterial({
        emissive: 0xffaa00,
        emissiveIntensity: 1.5,
        color: 0xffcc66
      })
    );
    bulb.rotation.x = Math.PI / 2;
    bulb.position.set(1.6, -0.15, 0);
    armPivot.add(bulb);

    // 🔥 THIS is the angle (clean + correct)
    armPivot.rotation.z = -Math.PI / 100;

    scene.add(g);
  }


  // Position poles on the curbs
  createLightPole(-8, 5, 0);
  createLightPole(-8, 25, 0);
  createLightPole(8, 5, Math.PI); // Rotate to face inward
  createLightPole(8, 25, Math.PI);

  // --- 11. CAMERA ---
  camera.position.set(0, 2.5, 35);
  state.cameraTargetPos.set(0, 2.5, 35);
  state.cameraLookAt.set(0, 6, 0);
  state.cameraTargetLookAt.set(0, 6, -10);
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

/* --- NEW AUTH LOGIC --- */

function updateAuthUI() {
  const authContainer = document.getElementById('authContainer');

  // Check local storage for user data
  // Assuming you store it as 'currentUser' object: { name: "...", image: "..." }
  const userStr = localStorage.getItem('currentUser') || sessionStorage.getItem('currentUser');

  if (userStr) {
    try {
      const user = JSON.parse(userStr);
      const profileImg = user.image ? `../uploads/profiles/${user.image}` : 'assets/default-user.png'; // Adjust path as needed
      const name = user.name || 'Shopper';

      authContainer.innerHTML = `
                <div class="profile-pill">
                    <img src="${profileImg}" alt="Profile" class="nav-profile-img">
                    <span class="profile-name">${name}</span>
                    <button id="btnLogout" class="btn-logout" title="Logout">
                        <i class="fas fa-sign-out-alt"></i>
                    </button>
                </div>
            `;

      // Attach Logout Event
      document.getElementById('btnLogout').addEventListener('click', () => {
        localStorage.removeItem('currentUser');
        sessionStorage.removeItem('currentUser');

        const returnUrl = encodeURIComponent(window.location.href);
        window.location.href = `../customerLogin.php?return_url=${returnUrl}`;
      });

    } catch (e) {
      console.error("Error parsing user data", e);
      renderLoginButton(authContainer);
    }
  } else {
    renderLoginButton(authContainer);
  }
}

function renderLoginButton(container) {
  const returnUrl = encodeURIComponent(window.location.href);
  container.innerHTML = `
    <a href="../customerLogin.php?return_url=${returnUrl}" class="btn-login-nav">
      Login
    </a>
  `;
}


// Call this when the script loads
updateAuthUI();

onResize();
await loadSuppliers();
createOutside();
setupSearchAndFloorSelector(); // Initialize search and floor selector
animate();
