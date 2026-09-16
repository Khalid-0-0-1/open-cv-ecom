const API = "api";

// ---------- Load products into admin list ----------
async function loadAdminProducts() {
  const list = document.getElementById("admin-product-list");
  list.innerHTML = `<p style="color:#aaa">Loading…</p>`;

  try {
    const res = await fetch(`${API}/get_products.php`);
    const products = await res.json();

    if (!Array.isArray(products) || products.length === 0) {
      list.innerHTML = `<p style="color:#aaa">No products yet. Add one above.</p>`;
      return;
    }

    list.innerHTML = "";
    products.forEach((p) => {
      const box = document.createElement("div");
      box.className = "product-box";
      if (p.stock <= 0) box.classList.add("sold-out");

      box.innerHTML = `
        <img src="${p.image}" alt="${p.title}" class="product-img" />
        <h2 class="product-title">${p.title}</h2>
        <span class="price">$${p.price}</span>
        <span class="stock-badge ${p.stock <= 0 ? 'out' : ''}">
          ${p.stock <= 0 ? 'SOLD OUT' : `Stock: ${p.stock}`}
        </span>
        <i class="bx bx-trash-alt add-cart" data-id="${p.id}" title="Delete"></i>
      `;
      list.appendChild(box);
    });

    list.querySelectorAll(".add-cart").forEach((btn) => {
      btn.addEventListener("click", () => deleteProduct(btn.dataset.id));
    });
  } catch (err) {
    list.innerHTML = `<p style="color:#fd4646">Could not load products: ${err.message}</p>`;
  }
}

// ---------- File input label ----------
const fileInput = document.getElementById("p-image");
const fileLabelText = document.getElementById("file-label-text");
fileInput.addEventListener("change", () => {
  fileLabelText.textContent =
    fileInput.files.length > 0 ? fileInput.files[0].name : "Choose image";
});

// ---------- Add product ----------
document.getElementById("product-form").addEventListener("submit", async (e) => {
  e.preventDefault();

  const form = e.target;
  const status = document.getElementById("upload-status");
  const submitBtn = document.getElementById("submit-btn");

  const formData = new FormData(form);

  status.textContent = "Uploading…";
  status.className = "upload-status";
  submitBtn.disabled = true;

  try {
    const res = await fetch(`${API}/add_product.php`, {
      method: "POST",
      body: formData,
    });
    const data = await res.json();

    if (!res.ok) {
      status.textContent = data.error || "Upload failed";
      status.className = "upload-status error";
    } else {
      status.textContent = "Product added ✓";
      status.className = "upload-status success";
      form.reset();
      fileLabelText.textContent = "Choose image";
      loadAdminProducts();
    }
  } catch (err) {
    status.textContent = "Network error: " + err.message;
    status.className = "upload-status error";
  } finally {
    submitBtn.disabled = false;
  }
});

// ---------- Delete product ----------
async function deleteProduct(id) {
  if (!confirm("Delete this product?")) return;

  try {
    const res = await fetch(`${API}/delete_product.php`, {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: `id=${encodeURIComponent(id)}`,
    });
    const data = await res.json();
    if (!res.ok) {
      alert(data.error || "Delete failed");
      return;
    }
    loadAdminProducts();
  } catch (err) {
    alert("Network error: " + err.message);
  }
}

// ---------- Init ----------
loadAdminProducts();

document.getElementById("logout-link").addEventListener("click", async (e) => {
  e.preventDefault();
  await fetch(`${API}/logout.php`, { method: "POST" });
  window.location.href = "login.html";
});