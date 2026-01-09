// 1. PLACE THESE AT THE VERY TOP OF script.js
function refreshCartDrawer(supplierId) {
    const container = document.getElementById('cartItemsContainer');
    const footer = document.getElementById('cartFooter');
    
    if (!container) return; // Safety check

    // Fetch the updated cart HTML
    fetch(`../../../utils/fetch_cart_drawer.php?supplier_id=${supplierId}`)
        .then(res => res.json())
        .then(data => {
            container.innerHTML = data.html;
            footer.innerHTML = data.footer;
            document.getElementById('cartDrawer').classList.add('open');
            document.getElementById('cartOverlay').classList.add('active');
        })
        .catch(err => console.error('Error fetching cart:', err));
}

// Function to close the drawer
function closeCart() {
    document.getElementById('cartDrawer').classList.remove('open');
    document.getElementById('cartOverlay').classList.remove('active');
}

document.addEventListener('DOMContentLoaded', function () {
    // 2. Add the Close Click Event
    // Toggle Cart Drawer
const cartBtn = document.querySelector('.navbar .fa-shopping-basket'); // Adjust selector to your cart icon
const drawer = document.querySelector('.cart-drawer');
const overlay = document.querySelector('.cart-overlay');
const closeBtn = document.querySelector('.close-btn');

function openCart() {
    drawer.classList.add('open');
    overlay.classList.add('active');
    loadCartItems(); // Call a function to fetch items from the 'cart' table
}

function closeCart() {
    drawer.classList.remove('open');
    overlay.classList.remove('active');
}

cartBtn.addEventListener('click', openCart);
closeBtn.addEventListener('click', closeCart);
overlay.addEventListener('click', closeCart);

    // 3. Update your existing "Add to Cart" logic for the Product List
    const addToCartButtons = document.querySelectorAll('.add-to-cart');
    addToCartButtons.forEach(button => {
        button.addEventListener('click', function (e) {
            e.preventDefault();
            const productId = this.getAttribute('data-product-id');
            const supplierId = this.getAttribute('data-supplier-id');
            const quantity = 1;

            const formData = new FormData();
            formData.append('product_id', productId);
            formData.append('supplier_id', supplierId);
            formData.append('quantity', quantity);

            fetch('../../../utils/add_to_cart.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // INSTEAD OF location.reload(), use your new drawer!
                    refreshCartDrawer(supplierId); 
                } else {
                    alert("Error: " + data.message);
                }
            });
        });
    });
});