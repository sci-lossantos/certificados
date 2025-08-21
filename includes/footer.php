</div>
    </div>

    <script>
        // Toggle sidebar en móvil
        document.getElementById('toggleSidebarMobile')?.addEventListener('click', function() {
            const sidebar = document.getElementById('logo-sidebar');
            sidebar.classList.toggle('-translate-x-full');
        });

        // Toggle dropdown de usuario
        document.getElementById('user-menu-button')?.addEventListener('click', function() {
            const dropdown = document.getElementById('dropdown-user');
            dropdown.classList.toggle('hidden');
        });

        // Cerrar dropdown al hacer click fuera
        document.addEventListener('click', function(event) {
            const button = document.getElementById('user-menu-button');
            const dropdown = document.getElementById('dropdown-user');
            
            if (button && dropdown && !button.contains(event.target) && !dropdown.contains(event.target)) {
                dropdown.classList.add('hidden');
            }
        });

        // Animaciones de entrada
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.card-shadow, .bg-white');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.6s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>
