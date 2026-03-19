// Add to cart functionality
function addToCart(productId, productName, productPrice, productImage) {
  // Send AJAX request to add item to cart
  $.ajax({
    url: "add_to_cart.php",
    type: "POST",
    data: {
      product_id: productId,
      product_name: productName,
      product_price: productPrice,
      product_image: productImage,
      quantity: 1,
    },
    success: (response) => {
      // Parse the JSON response
      const result = JSON.parse(response)

      if (result.status === "success") {
        // Show success message
        alert("Product added to cart successfully!")

        // Update cart count in navbar
        updateCartCount(result.cart_count)
      } else {
        // Show error message
        alert("Failed to add product to cart. Please try again.")
      }
    },
    error: () => {
      alert("An error occurred. Please try again later.")
    },
  })
}

// Update cart count in navbar
function updateCartCount(count) {
  const cartBadge = document.querySelector(".fa-shopping-cart + .badge")

  if (cartBadge) {
    cartBadge.textContent = count
  } else {
    const cartIcon = document.querySelector(".fa-shopping-cart")
    const badge = document.createElement("span")
    badge.className = "badge bg-danger"
    badge.textContent = count
    cartIcon.parentNode.appendChild(badge)
  }
}

// Update quantity in cart
function updateQuantity(productId, action) {
  $.ajax({
    url: "update_cart.php",
    type: "POST",
    data: {
      product_id: productId,
      action: action,
    },
    success: (response) => {
      // Reload the page to reflect changes
      location.reload()
    },
    error: () => {
      alert("An error occurred. Please try again later.")
    },
  })
}

// Product image gallery
$(document).ready(() => {
  $(".product-thumbnail").click(function () {
    const mainImg = $("#main-product-image")
    const newSrc = $(this).attr("src")

    mainImg.fadeOut(300, () => {
      mainImg.attr("src", newSrc)
      mainImg.fadeIn(300)
    })
  })

  // Form validation
  $(".needs-validation").submit(function (event) {
    if (this.checkValidity() === false) {
      event.preventDefault()
      event.stopPropagation()
    }

    $(this).addClass("was-validated")
  })
})

