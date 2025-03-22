document.addEventListener("DOMContentLoaded", function () {
    const loginForm = document.getElementById("loginForm");
    const signupForm = document.getElementById("signupForm");
    const resetPasswordForm = document.getElementById("resetPasswordForm");

    function getUser(email) {
        const users = JSON.parse(localStorage.getItem("users")) || [];
        return users.find(user => user.email === email);
    }

    function saveUser(user) {
        const users = JSON.parse(localStorage.getItem("users")) || [];
        users.push(user);
        localStorage.setItem("users", JSON.stringify(users));
    }

    if (loginForm) {
        loginForm.addEventListener("submit", function (event) {
            event.preventDefault();
            const emailInput = document.getElementById("email");
            const passwordInput = document.getElementById("password");

            if (!emailInput || !passwordInput) return;

            const email = emailInput.value.trim();
            const password = passwordInput.value;
            const user = getUser(email);

            if (user && user.password === password) {
                alert("Connexion réussie !");
                localStorage.setItem("loggedInUser", email);
                window.location.href = "../../sec/secacceuil.html"; // Redirection corrigée
            } else {
                alert("Email ou mot de passe incorrect !");
            }
        });
    }

    if (signupForm) {
        signupForm.addEventListener("submit", function (event) {
            event.preventDefault();
            const emailInput = document.getElementById("newEmail");
            const passwordInput = document.getElementById("newPassword");
            const confirmPasswordInput = document.getElementById("confirmPassword");

            if (!emailInput || !passwordInput || !confirmPasswordInput) return;

            const newEmail = emailInput.value.trim();
            const newPassword = passwordInput.value;
            const confirmPassword = confirmPasswordInput.value;

            if (!newEmail || !newPassword || !confirmPassword) {
                alert("Veuillez remplir tous les champs.");
                return;
            }

            if (newPassword !== confirmPassword) {
                alert("Les mots de passe ne correspondent pas !");
                return;
            }

            if (getUser(newEmail)) {
                alert("Un compte avec cet email existe déjà !");
                return;
            }

            saveUser({ email: newEmail, password: newPassword });

            alert("Compte créé avec succès ! Vous pouvez maintenant vous connecter.");
            window.location.href = "login.html";
        });
    }

    if (resetPasswordForm) {
        resetPasswordForm.addEventListener("submit", function (event) {
            event.preventDefault();
            const emailInput = document.getElementById("resetEmail");

            if (!emailInput) return;

            const resetEmail = emailInput.value.trim();
            const users = JSON.parse(localStorage.getItem("users")) || [];
            const userIndex = users.findIndex(user => user.email === resetEmail);

            if (userIndex === -1) {
                alert("Aucun compte trouvé avec cet email !");
                return;
            }

            const newPassword = prompt("Entrez votre nouveau mot de passe :");
            if (newPassword) {
                users[userIndex].password = newPassword;
                localStorage.setItem("users", JSON.stringify(users));
                alert("Mot de passe réinitialisé avec succès !");
                window.location.href = "login.html";
            } else {
                alert("La réinitialisation a été annulée.");
            }
        });
    }

    const logoutButton = document.getElementById("logout");
    if (logoutButton) {
        logoutButton.addEventListener("click", function () {
            localStorage.removeItem("loggedInUser");
            window.location.href = "index.html";
        });
    }
});
