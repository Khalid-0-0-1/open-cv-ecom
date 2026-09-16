// ---------- Cart Open / Close ----------
const cartIcon = document.querySelector("#cart-icon");
const cart = document.querySelector(".cart");
const closeCart = document.querySelector("#close-cart");

cartIcon.onclick = () => cart.classList.add("active");
closeCart.onclick = () => cart.classList.remove("active");

// ---------- Load products from JSON ----------
const API = "api";

async function loadProducts() {
  const shopContent = document.getElementById("shop-content");
  shopContent.innerHTML = `<p style="color:#aaa">Loading…</p>`;

  try {
    const res = await fetch(`${API}/get_products.php`);
    const products = await res.json();

    if (!Array.isArray(products) || products.length === 0) {
      shopContent.innerHTML = `<p style="color:#aaa">No products available.</p>`;
      return;
    }

    renderProducts(products);
  } catch (err) {
    shopContent.innerHTML = `<p style="color:#fd4646">Could not load products.</p>`;
  }
}
function renderProducts(products) {
  const shopContent = document.getElementById("shop-content");
  shopContent.innerHTML = "";

  products.forEach((p) => {
    const soldOut = p.stock <= 0;
    const box = document.createElement("div");
    box.className = "product-box" + (soldOut ? " sold-out" : "");
    box.dataset.id = p.id;
    box.dataset.stock = p.stock;
    box.innerHTML = `
      <img src="${p.image}" alt="${p.title}" class="product-img" />
      <h2 class="product-title">${p.title}</h2>
      <span class="price">$${p.price}</span>
      <span class="stock-badge ${soldOut ? 'out' : ''}">
        ${soldOut ? 'SOLD OUT' : `Stock: ${p.stock}`}
      </span>
      <i class="bx bx-shopping-bag add-cart" ${soldOut ? 'style="pointer-events:none;opacity:0.4"' : ''}></i>
    `;
    shopContent.appendChild(box);
  });

  document.querySelectorAll(".add-cart").forEach((btn) => {
    btn.addEventListener("click", addCartClicked);
  });
}

// ---------- Add to Cart ----------
function addCartClicked(event) {
  const button = event.target;
  const shopProducts = button.parentElement;
  const id = shopProducts.dataset.id;
  const title = shopProducts.querySelector(".product-title").innerText;
  const price = shopProducts.querySelector(".price").innerText;
  const productImg = shopProducts.querySelector(".product-img").src;
  const stock = Number(shopProducts.dataset.stock) || 0;

  addProductToCart(id, title, price, productImg, stock);
  updateTotal();
  saveCartItems();
  updateCartBadge();
}

function addProductToCart(id, title, price, productImg, stock) {
  const cartItems = document.querySelector(".cart-content");

  const existingNames = cartItems.querySelectorAll(".cart-product-title");
  for (const name of existingNames) {
    if (name.innerText === title) {
      alert("You have already added this item to cart.");
      return;
    }
  }

  const cartShopBox = document.createElement("div");
  cartShopBox.classList.add("cart-box");
  cartShopBox.dataset.id = id;
  cartShopBox.dataset.stock = stock;
  cartShopBox.innerHTML = `
    <img src="${productImg}" alt="" class="cart-img" />
    <div class="detail-box">
      <div class="cart-product-title">${title}</div>
      <div class="cart-price">${price}</div>
      <div class="cart-stock-left">${stock} left in stock</div>
      <input type="number" value="1" min="1" max="${stock}" class="cart-quantity" />
    </div>
    <i class="bx bx-trash-alt cart-remove"></i>
  `;
  cartItems.appendChild(cartShopBox);

  cartShopBox.querySelector(".cart-remove").addEventListener("click", removeCartItem);
  cartShopBox.querySelector(".cart-quantity").addEventListener("change", quantityChanged);

  saveCartItems();
}

// ---------- Remove / Quantity ----------
function removeCartItem(event) {
  event.target.parentElement.remove();
  updateTotal();
  saveCartItems();
  updateCartBadge();
}

function quantityChanged(event) {
  const input = event.target;
  const max = Number(input.max) || Infinity;
  if (isNaN(input.value) || input.value <= 0) input.value = 1;
  if (Number(input.value) > max) input.value = max;
  updateTotal();
  saveCartItems();
  updateCartBadge();
}

// ---------- Total ----------
function updateTotal() {
  const cartContent = document.querySelector(".cart-content");
  const cartBoxes = cartContent.querySelectorAll(".cart-box");
  let total = 0;

  cartBoxes.forEach((box) => {
    const priceEl = box.querySelector(".cart-price");
    const qtyEl = box.querySelector(".cart-quantity");
    const price = parseFloat(priceEl.innerText.replace("$", ""));
    const quantity = Number(qtyEl.value);
    total += price * quantity;
  });

  total = Math.round(total * 100) / 100;
  document.querySelector(".total-price").innerText = "$" + total;
  localStorage.setItem("cartTotal", total);
}

// ---------- Badge count ----------
function updateCartBadge() {
  const cartBoxes = document.querySelectorAll(".cart-box");
  cartIcon.setAttribute("data-quantity", cartBoxes.length);
}

// ---------- LocalStorage ----------
function saveCartItems() {
  const cartBoxes = document.querySelectorAll(".cart-box");
  const cartItems = [];

  cartBoxes.forEach((box) => {
    cartItems.push({
      id: box.dataset.id,
      title: box.querySelector(".cart-product-title").innerText,
      price: box.querySelector(".cart-price").innerText,
      quantity: box.querySelector(".cart-quantity").value,
      productImg: box.querySelector(".cart-img").src,
      stock: box.dataset.stock,
    });
  });

  localStorage.setItem("cartItems", JSON.stringify(cartItems));
}

function loadCartItems() {
  const stored = localStorage.getItem("cartItems");
  if (stored) {
    JSON.parse(stored).forEach((item) => {
      addProductToCart(item.id, item.title, item.price, item.productImg, Number(item.stock) || 0);
      const boxes = document.querySelectorAll(".cart-box");
      const lastBox = boxes[boxes.length - 1];
      lastBox.querySelector(".cart-quantity").value = item.quantity;
    });
  }

  const cartTotal = localStorage.getItem("cartTotal");
  if (cartTotal) {
    document.querySelector(".total-price").innerText = "$" + cartTotal;
  }

  updateTotal();
  updateCartBadge();
}
// ---------- "Pay Now" () ----------
document.querySelector(".btn-buy").addEventListener("click", async () => {
  const cartBoxes = document.querySelectorAll(".cart-box");
  if (cartBoxes.length === 0) {
    alert("Your cart is empty.");
    return;
  }

  // Byg payload til API'et
  const items = [];
  cartBoxes.forEach((box) => {
    items.push({
      id: Number(box.dataset.id),
      quantity: Number(box.querySelector(".cart-quantity").value),
    });
  });

  try {
    const res = await fetch("api/buy_products.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(items),
    });
    const data = await res.json();

    if (!res.ok) {
      alert(data.error || "Purchase failed");
      return;
    }

    // Tøm kurven
    document.querySelector(".cart-content").innerHTML = "";
    localStorage.removeItem("cartItems");
    localStorage.removeItem("cartTotal");
    updateTotal();
    updateCartBadge();

    alert("Order placed! Thank you.");
    loadProducts(); // Genindlæs så stock opdateres og udsolgte markeres
  } catch (err) {
    alert("Network error: " + err.message);
  }
});



// ---------- Init ----------
if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", init);
} else {
  init();
}

async function init() {
  await loadProducts();
  loadCartItems();
}