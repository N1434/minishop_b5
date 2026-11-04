// ===== 1. Hàm tiện ích =====

// Định dạng số thành tiền Việt: 250000 -> "250.000 đ"
function formatPrice(amount) {
    return amount.toLocaleString("vi-VN") + " đ";
}

// ===== 3. Trang giỏ hàng: tự tính thành tiền và tổng cộng =====
function updateCartTotals(table) {
    let total = 0;
    table.querySelectorAll("tbody tr").forEach(function (row) {
        const price = Number(row.dataset.price);
        const qty = Number(row.querySelector(".qty").value);
        const lineTotal = price * qty;
        row.querySelector(".line-total").textContent = formatPrice(lineTotal);
        total += lineTotal;
    });
    document.querySelector("#cart-total").textContent = formatPrice(total);
}

const cartTable = document.querySelector(".cart-table");
if (cartTable) {
    // Tính lại mỗi khi người dùng đổi số lượng ở bất kỳ ô nào trong bảng
    cartTable.addEventListener("input", function () {
        updateCartTotals(cartTable);
    });
    // Tính lần đầu khi trang vừa mở
    updateCartTotals(cartTable);
}

// ===== 4. Form đăng ký: hai mật khẩu phải khớp nhau =====
const registerForm = document.querySelector("#register-form");
if (registerForm) {
    registerForm.addEventListener("submit", function (event) {
        const password = document.querySelector("#password").value;
        const password2 = document.querySelector("#password2").value;
        const error = document.querySelector("#password-error");

        if (password !== password2) {
            event.preventDefault();
            error.textContent = "Hai mật khẩu không khớp nhau.";
        } else {
            error.textContent = "";
        }
    });
}
