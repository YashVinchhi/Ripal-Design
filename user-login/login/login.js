const eyeicon = document.getElementById("eyeicon");
const password = document.getElementById("password");
const form = document.querySelector('.login-form');
const statusBox = document.querySelector('.status');

// Add smooth page transition for signup link
const signupLink = document.querySelector('.account a[href="Signup.html"]');
if (signupLink) {
    signupLink.addEventListener('click', (event) => {
        event.preventDefault();
        document.body.classList.add('page-out');
        setTimeout(() => {
            window.location.href = 'Signup.html';
        }, 400);
    });
}

if (eyeicon && password) {
    eyeicon.addEventListener("click", () => {
        const isHidden = password.type === "password";
        password.type = isHidden ? "text" : "password";
        eyeicon.src = isHidden ? "eye/eye-on.svg" : "eye/eye-off.svg";
    });
}

if (form) {
    form.addEventListener('submit', (event) => {
        event.preventDefault();
        const username = form.elements.username.value.trim();
        const pwd = form.elements.password.value.trim();

        if (!username && !pwd) {
            if (statusBox) statusBox.textContent = '! Please enter username and password.';
                return;
            }

        else if(!username) {
            if (statusBox) statusBox.textContent = '! Please enter username.';
            return;
        }
        else if(!pwd) {
            if (statusBox) statusBox.textContent = '! Please enter password.';
            return;
        }

        if (statusBox) statusBox.textContent = 'Login.';
    });
}
