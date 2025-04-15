<?php
session_start();

// 1. Connexion DB (adaptez à votre configuration)
$host = 'localhost';
$dbname = 'jurishub';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
} catch (PDOException $e) {
    die("Erreur connexion : " . $e->getMessage());
}

// 2. Traitement formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // 3. Requête simple (mot de passe en clair)
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND password = ?");
    $stmt->execute([$email, $password]);
    $user = $stmt->fetch();
    
    if ($user) {
        $_SESSION['user'] = $user;
        
        switch ($user['role']) {
            case 'client':
                header('Location: ../../client/indexcl.php');
                break;
            case 'avocat':
                header('Location: ../../avocat/indexav.php');
                break;
            default:
                header('Location: ../../sec/profil.php');
        }
        exit();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <style>
        :root {
  --primary-color: #2c3e50;
  --secondary-color: #3498db;
  --accent-color: #e74c3c;
  --light-gray: #f5f5f5;
  --text-color: #333;
  --white: #ffffff;
  --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  --transition: all 0.3s ease;
}

body {
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  background-color: #f8f9fa;
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
  margin: 0;
  padding: 20px;
  color: var(--text-color);
}

.login-container {
  background-color: var(--white);
  border-radius: 10px;
  box-shadow: var(--box-shadow);
  padding: 2.5rem;
  width: 100%;
  max-width: 400px;
  transition: var(--transition);
}

h2 {
  color: var(--primary-color);
  text-align: center;
  margin-bottom: 1.5rem;
  font-weight: 600;
}

.error {
  color: var(--accent-color);
  background-color: #fee;
  padding: 12px;
  border-radius: 5px;
  margin-bottom: 1.5rem;
  text-align: center;
  border: 1px solid #fdd;
}

form {
  display: flex;
  flex-direction: column;
  gap: 1.2rem;
}

input {
  padding: 12px 15px;
  border: 1px solid #ddd;
  border-radius: 5px;
  font-size: 16px;
  transition: var(--transition);
  width: 100%;
  box-sizing: border-box;
}

input:focus {
  outline: none;
  border-color: var(--secondary-color);
  box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
}

button {
  background-color: var(--secondary-color);
  color: white;
  border: none;
  padding: 14px;
  border-radius: 5px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: var(--transition);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

button:hover {
  background-color: #2980b9;
  transform: translateY(-2px);
}

.links {
  margin-top: 1.5rem;
  text-align: center;
  font-size: 14px;
}

.links a {
  color: var(--secondary-color);
  text-decoration: none;
  transition: var(--transition);
}

.links a:hover {
  text-decoration: underline;
}

/* Animation */
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}

.login-container {
  animation: fadeIn 0.5s ease-out;
}
body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            color: #2c3e50;
        }
        
        .login-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            padding: 2.5rem;
            width: 100%;
            max-width: 420px;
            margin: 20px;
            transition: all 0.3s ease;
        }
        
        h2 {
            text-align: center;
            margin-bottom: 2rem;
            color: #2c3e50;
            font-size: 1.8rem;
        }
        
        /* Styles du formulaire */
        .login-form {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        
        .input-group {
            position: relative;
        }
        
        .input-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #2c3e50;
        }
        
        .input-group input {
            width: 100%;
            padding: 14px 16px 14px 40px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .input-group input:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }
        
        .input-icon {
            position: absolute;
            left: 15px;
            top: 40px;
            color: #7f8c8d;
        }
        
        /* Bouton de connexion */
        .login-button {
            background: #3498db;
            color: white;
            border: none;
            padding: 14px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .login-button:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }
        
        /* Pied de formulaire */
        .form-footer {
            display: flex;
            justify-content: space-between;
            margin-top: 1rem;
            font-size: 14px;
        }
        
        .forgot-password, .register-link {
            color: #3498db;
            text-decoration: none;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .forgot-password:hover, .register-link:hover {
            color: #2980b9;
            text-decoration: underline;
        }
        
        /* Message d'erreur */
        .error-message {
            background: #fee;
            color: #e74c3c;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 8px;
            border: 1px solid #fdd;
        }
        
        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .login-container {
            animation: fadeIn 0.4s ease-out;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Connexion</h2>
        
        <?php if (!empty($error)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="post" class="login-form">
            <div class="input-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="votre@email.com" required>
                <i class="fas fa-envelope input-icon"></i>
            </div>
            
            <div class="input-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" placeholder="••••••••" required>
                <i class="fas fa-lock input-icon"></i>
            </div>
            
            <button type="submit" class="login-button">
                <i class="fas fa-sign-in-alt"></i> Se connecter
            </button>
            
            <div class="form-footer">
                <a href="mdp-oublie.php" class="forgot-password">
                    <i class="fas fa-question-circle"></i> Mot de passe oublié ?
                </a>
                <a href="inscription.php" class="register-link">
                    <i class="fas fa-user-plus"></i> Créer un compte
                </a>
            </div>
        </form>
    </div>
    </body>
</html>