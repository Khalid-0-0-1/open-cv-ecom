<?php
require __DIR__ . '/api/auth.php';

if (!isAdmin()) {
    header('Location: login.html');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin — Products</title>
    <link rel="stylesheet" href="style.css" />
    <link
      href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css"
      rel="stylesheet"
    />
  </head>
  <body>
    <header>
      <div class="nav container">
        <a href="index.html" class="logo">Ecommerce</a>
        <div class="nav-right">
          <a href="index.html" class="admin-link">
            <i class="bx bx-store"></i> Back to shop
          </a>
          <a href="#" id="logout-link" class="admin-link" title="Log out">
            <i class="bx bx-log-out"></i>
          </a>
        </div>
      </div>
    </header>

    <section class="shop container">
      <h2 class="section-title">Admin — Add Product</h2>

      <form id="product-form" class="admin-form" enctype="multipart/form-data">
  <input type="text" name="title" id="p-title" placeholder="Product title" required />
  <input type="number" name="price" id="p-price" placeholder="Price" step="0.01" min="0" required />
  <input type="number" name="stock" id="p-stock" placeholder="Stock (qty)" min="0" required />

  <label for="p-image" class="file-label">
    <i class="bx bx-image-add"></i>
    <span id="file-label-text">Choose image</span>
    <input type="file" name="image" id="p-image" accept="image/*" required />
  </label>

  <button type="submit" class="btn-buy" id="submit-btn">Add Product</button>
</form>

      <div id="upload-status" class="upload-status"></div>

      <h2 class="section-title" style="margin-top: 3rem">Current Products</h2>
      <div id="admin-product-list" class="shop-content"></div>
    </section>

    <script src="admin.js"></script>
  </body>
</html>
