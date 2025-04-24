/**
 * Fonctions communes pour l'authentification
 * Script utilise par login.html et register.html
 */

// Fonction pour valider la complexite du mot de passe
function validatePasswordComplexity(password) {
    const minLength = 8;
    const hasLowerCase = /[a-z]/.test(password);
    const hasUpperCase = /[A-Z]/.test(password);
    const hasDigit = /\d/.test(password);
    const hasSpecialChar = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password);
    
    // Verifier si toutes les conditions sont remplies
    const isValid = (
      password.length >= minLength && 
      hasLowerCase && 
      hasUpperCase && 
      hasDigit && 
      hasSpecialChar
    );
    
    // Mise a jour visuelle de l'indicateur
    const strengthIndicator = document.getElementById('passwordStrength');
    if (strengthIndicator) {
      if (isValid) {
        strengthIndicator.className = 'password-strength valid';
      } else {
        strengthIndicator.className = 'password-strength invalid';
      }
    }
    
    // Retourne true seulement si toutes les conditions sont remplies
    return isValid;
}

// Fonction pour basculer la visibilite du mot de passe
function togglePasswordVisibility(inputId) {
    const input = document.getElementById(inputId);
    if (input.type === "password") {
        input.type = "text";
    } else {
        input.type = "password";
    }
}

// Verifier si l'email est valide (domaine @bssl.com)
function isValidEmail(email, errorElement) {
    const pattern = /@bssl\.com$/i;
    if (pattern.test(email) === true) {
        errorElement.style.display = "none";
        return true;
    } else {
        errorElement.style.display = "block";
        return false;
    }
}

// Reinitialiser tous les messages d'erreur
function resetErrorMessages() {
    const errorMessages = document.querySelectorAll('.error-message');
    errorMessages.forEach(msg => {
        msg.style.display = 'none';
    });

    // Reinitialiser des elements specifiques si presents
    const detailedError = document.getElementById("detailedError");
    const successMessage = document.getElementById("successMessage");
    const message = document.getElementById("message");

    if (detailedError) detailedError.style.display = 'none';
    if (successMessage) successMessage.style.display = 'none';
    if (message) {
        message.textContent = '';
        message.className = '';
    }
}

// Verifier si les mots de passe correspondent
function checkPasswordMatch(passwordField, confirmPasswordField, matchMsg, notMatchMsg) {
    if (passwordField.value === confirmPasswordField.value && passwordField.value !== "") {
        matchMsg.style.display = "block";
        notMatchMsg.style.display = "none";
        return true;
    } else if (confirmPasswordField.value !== "") {
        matchMsg.style.display = "none";
        notMatchMsg.style.display = "block";
        return false;
    } else {
        matchMsg.style.display = "none";
        notMatchMsg.style.display = "none";
        return false;
    }
}

// Initialiser les gestionnaires pour le formulaire de connexion
function initLoginForm() {
    const form = document.getElementById("loginForm");
    const message = document.getElementById("message");
    const emailField = document.getElementById("mail");
    const passwordField = document.getElementById("password");
    const submitBtn = document.getElementById("submitBtn");
    const mailNotMatchMsg = document.querySelector(".email-not-match");

    // Ajouter un ecouteur d'evenements pour verifier l'email en temps reel
    emailField.addEventListener("input", () => {
        if (emailField.value.trim() !== "") {
            isValidEmail(emailField.value.trim(), mailNotMatchMsg);
        } else {
            mailNotMatchMsg.style.display = "none";
        }
    });

    form.addEventListener("submit", async (e) => {
        e.preventDefault();

        const mail = emailField.value.trim();
        const password = passwordField.value;

        if (!mail || !password) {
            message.textContent = "Veuillez remplir tous les champs.";
            message.className = "error";
            return;
        }

        if (!isValidEmail(mail, mailNotMatchMsg)) {
            message.textContent = "Seuls les emails @bssl.com sont autorisés.";
            message.className = "error";
            return;
        }

        // Desactiver le bouton pendant la requete
        submitBtn.disabled = true;
        message.textContent = "Connexion en cours...";
        message.className = "";

        try {
            const res = await fetch("auth.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    mail: mail,
                    password: password,
                    action: "login"
                })
            });

            const data = await res.json();

            if (data.success) {
                message.textContent = "Connexion réussie.";
                message.className = "success";

                // Redirection vers la page index.html
                window.location.href = "/index.html";
            } else {
                // Message d'erreur generique
                message.textContent = "Identifiants incorrects.";
                message.className = "error";
            }
        } catch (err) {
            console.error(err);
            message.textContent = "Erreur de connexion au serveur.";
            message.className = "error";
        } finally {
            // Reactiver le bouton
            submitBtn.disabled = false;
        }
    });
}

// Initialiser les gestionnaires pour le formulaire d'inscription
function initRegisterForm() {
    const form = document.getElementById("registerForm");
    const message = document.getElementById("message");
    const detailedError = document.getElementById("detailedError");
    const successMessage = document.getElementById("successMessage");
    const passwordField = document.getElementById("password");
    const confirmPasswordField = document.getElementById("confirmPassword");
    const passwordMatchMsg = document.querySelector(".password-match");
    const passwordNotMatchMsg = document.querySelector(".password-not-match");
    const mailNotMatchMsg = document.querySelector(".email-not-match");
    const emailExistsError = document.getElementById("emailExistsError");
    const emailField = document.getElementById("mail");
    const submitBtn = document.getElementById("submitBtn");

    // Fonction pour verifier la validite du formulaire
    function checkFormValidity() {
        const isEmailValid = isValidEmail(emailField.value.trim(), mailNotMatchMsg);
        const isPasswordValid = validatePasswordComplexity(passwordField.value);
        const isPasswordMatching = checkPasswordMatch(passwordField, confirmPasswordField, passwordMatchMsg, passwordNotMatchMsg);

        if (isEmailValid && isPasswordValid && isPasswordMatching) {
            submitBtn.disabled = false;
        } else {
            submitBtn.disabled = true;
        }
    }

    // Ajouter les ecouteurs d'evenements pour la verification en temps reel
    passwordField.addEventListener("input", () => {
        validatePasswordComplexity(passwordField.value);
        checkFormValidity();
    });
    
    confirmPasswordField.addEventListener("input", checkFormValidity);
    
    emailField.addEventListener("input", () => {
        // Reinitialiser le message d'email existant a chaque changement
        if (emailExistsError) {
            emailExistsError.style.display = "none";
        }

        // Verifier la validite de l'email si le champ n'est pas vide
        if (emailField.value.trim() !== "") {
            isValidEmail(emailField.value.trim(), mailNotMatchMsg);
        } else {
            // Si le champ est vide, masquer le message d'erreur
            mailNotMatchMsg.style.display = "none";
        }

        // Verifier la validite globale du formulaire
        checkFormValidity();
    });

    form.addEventListener("submit", async (e) => {
        e.preventDefault();
        resetErrorMessages();

        const mail = emailField.value.trim();
        const password = passwordField.value;
        const confirmPassword = confirmPasswordField.value;

        if (!mail || !password) {
            message.textContent = "Veuillez remplir tous les champs.";
            message.className = "error";
            return;
        }

        if (!isValidEmail(mail, mailNotMatchMsg)) {
            message.textContent = "Format d'email invalide.";
            message.className = "error";
            mailNotMatchMsg.style.display = "block";
            return;
        }

        if (!validatePasswordComplexity(password)) {
            message.textContent = "Le mot de passe doit contenir au moins 8 caractères avec au moins une minuscule, une majuscule, un chiffre et un caractère spécial";
            message.className = "error";
            return;
        }

        if (password !== confirmPassword) {
            message.textContent = "Les mots de passe ne correspondent pas.";
            message.className = "error";
            passwordNotMatchMsg.style.display = "block";
            return;
        }

        // Afficher un indicateur de chargement
        message.textContent = "Création du compte en cours...";
        message.className = "";
        submitBtn.disabled = true;

        try {
            // Envoyer la requete d'inscription
            // Utiliser fetch pour envoyer les donnees au serveur
            const res = await fetch("auth.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    mail: mail,
                    password: password,
                    action: "register"
                })
            });

            // Verifier d'abord si la reponse existe
            let data;
            try {
                data = await res.json();
            } catch (jsonError) {
                console.error("Erreur lors du parsing JSON:", jsonError);
                throw new Error("Format de réponse invalide");
            }

            // Si la reponse est un succes
            if (data.success) {
                message.textContent = "Compte créé avec succès !";
                message.className = "success";
                successMessage.textContent = "Vous allez être redirigé vers la page de connexion...";
                successMessage.style.display = "block";
                form.reset();
                passwordMatchMsg.style.display = "none";

                // Rediriger vers la page de connexion apres 3 secondes
                setTimeout(() => {
                    window.location.href = "login.html";
                }, 3000);
            } else {
                // Erreur provenant du serveur
                if (data.error) {
                    message.textContent = data.error;
                    message.className = "error";

                    // Si l'email existe deja
                    if (data.error.includes("Cet email est déjà utilisé")) {
                        if (emailExistsError) {
                            emailExistsError.style.display = "block";
                        } else {
                            // Si l'element n'existe pas, creer un message d'erreur a cote du champ
                            const errorDiv = document.createElement("div");
                            errorDiv.className = "email-exists error-message";
                            errorDiv.id = "emailExistsError";
                            errorDiv.textContent = "Cet email est déjà utilisé";
                            errorDiv.style.display = "block";
                            emailField.parentNode.appendChild(errorDiv);
                        }
                        emailField.focus();
                    }
                    // Si c'est une erreur de domaine d'email
                    else if (data.error.includes("@bssl.com")) {
                        mailNotMatchMsg.style.display = "block";
                        emailField.focus();
                    }
                } else {
                    // Erreur generique si pas de message specifique
                    message.textContent = "Erreur lors de la création du compte";
                    message.className = "error";
                }
            }
        } catch (err) {
            console.error("Erreur de connexion:", err);

            // Essayer de recuperer la reponse meme en cas d'erreur
            message.textContent = "Erreur, votre mail n'est peut être pas enregistré";
            message.className = "error";

            // Verifier si l'erreur contient une information
            const errorText = err.toString().toLowerCase();
            if (errorText.includes("email") && errorText.includes("déjà utilisé")) {
                if (emailExistsError) {
                    emailExistsError.style.display = "block";
                }
                emailField.focus();
            }
        } finally {
            submitBtn.disabled = false;
        }
    });

    // Initialiser l'etat du bouton
    checkFormValidity();
}