<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

$error = '';

if ($_POST) {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($auth->login($email, $password)) {
        header("Location: dashboard.php");
        exit();
    } else {
        $error = 'Credenciales incorrectas';
    }
}

$page_title = 'Iniciar Sesión';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - ESIBOC-DNBC</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #1e2a4a 0%, #2c3a60 100%);
        }
        .glass-effect {
            backdrop-filter: blur(10px);
            background-color: rgba(255, 255, 255, 0.9);
        }
        .btn-primary {
            background: linear-gradient(135deg, #e63946 0%, #d32f2f 100%);
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(230, 57, 70, 0.2);
        }
        .input-focus {
            background-color: #ffffff !important;
            border: 1px solid #d1d5db !important;
        }
        .input-focus:focus {
            border-color: #1e2a4a !important;
            box-shadow: 0 0 0 3px rgba(30, 42, 74, 0.1) !important;
            background-color: #ffffff !important;
        }
    </style>
</head>
<body class="gradient-bg min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full">
        <!-- Logo y título -->
        <div class="text-center mb-8">
            <div class="mx-auto w-20 h-20 bg-white rounded-full flex items-center justify-center mb-4 shadow-lg">
                <i class="fas fa-fire text-3xl" style="color: #e63946;"></i>
            </div>
            <h1 class="text-4xl font-bold text-white mb-2">PNCB-DNBC</h1>
            <p class="text-white text-opacity-80">Plataforma Nacional de Certificación Bomberil (PNCB)</p>
        </div>

        <!-- Formulario de login -->
        <div class="glass-effect rounded-2xl shadow-2xl p-8">
            <div class="text-center mb-6">
                <h2 class="text-2xl font-bold" style="color: #1e2a4a;">Iniciar Sesión</h2>
                <p class="mt-2" style="color: #6c757d;">Ingresa tus credenciales para acceder</p>
            </div>

            <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-4">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6">
                <div>
                    <label for="email" class="block text-sm font-medium mb-2" style="color: #1e2a4a;">
                        <i class="fas fa-envelope mr-2"></i>Correo Electrónico
                    </label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        required 
                        class="input-focus bg-white w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none transition-all duration-300"
                        placeholder="tu@email.com"
                        value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                        style="background-color: #ffffff !important;"
                    >
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium mb-2" style="color: #1e2a4a;">
                        <i class="fas fa-lock mr-2"></i>Contraseña
                    </label>
                    <div class="relative">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            required 
                            class="input-focus bg-white w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none transition-all duration-300 pr-12"
                            placeholder="••••••••"
                            style="background-color: #ffffff !important;"
                        >
                        <button 
                            type="button" 
                            onclick="togglePassword()" 
                            class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700"
                        >
                            <i id="password-icon" class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <label class="flex items-center">
                        <input type="checkbox" class="rounded border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50" style="color: #1e2a4a;">
                        <span class="ml-2 text-sm" style="color: #6c757d;">Recordarme</span>
                    </label>
                    <a href="forgot-password.php" class="text-sm transition-colors" style="color: #1e2a4a;" onmouseover="this.style.color='#e63946'" onmouseout="this.style.color='#1e2a4a'">
                        ¿Olvidaste tu contraseña?
                    </a>
                </div>

                <button 
                    type="submit" 
                    class="btn-primary w-full text-white font-semibold py-3 px-4 rounded-lg focus:outline-none focus:ring-4"
                    style="focus:ring-color: rgba(230, 57, 70, 0.3);"
                >
                    <i class="fas fa-sign-in-alt mr-2"></i>Iniciar Sesión
                </button>
            </form>

            <!-- Información de acceso demo -->
            <div class="mt-6 p-4 rounded-lg border" style="background-color: rgba(30, 42, 74, 0.05); border-color: #1e2a4a;">
                <h3 class="text-sm font-semibold mb-2" style="color: #1e2a4a;">Acceso de Prueba:</h3>
                <p class="text-xs" style="color: #6c757d;">
                    <strong>Email:</strong> admin@esiboc.gov.co<br>
                    <strong>Contraseña:</strong> password
                </p>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-8 text-white text-opacity-70">
            <p class="text-sm">© 2024 ESIBOC-DNBC. Todos los derechos reservados.</p>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const passwordIcon = document.getElementById('password-icon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                passwordIcon.classList.remove('fa-eye');
                passwordIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                passwordIcon.classList.remove('fa-eye-slash');
                passwordIcon.classList.add('fa-eye');
            }
        }

        // Animación de entrada
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('.glass-effect');
            form.style.opacity = '0';
            form.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                form.style.transition = 'all 0.6s ease';
                form.style.opacity = '1';
                form.style.transform = 'translateY(0)';
            }, 100);
        });
    </script>
</body>
</html>
