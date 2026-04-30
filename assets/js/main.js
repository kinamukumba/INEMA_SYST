document.addEventListener("DOMContentLoaded", () => {
    // Smooth scroll para links da navbar
    const links = document.querySelectorAll('.nav-links a');
    
    links.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href').substring(1);
            const targetSection = document.getElementById(targetId);
            
            if (targetSection) {
                // Remove estado ativo de todos
                links.forEach(l => l.classList.remove('active'));
                // Adiciona ao clicado
                this.classList.add('active');
                
                window.scrollTo({
                    top: targetSection.offsetTop - 70, // Compensação do header
                    behavior: 'smooth'
                });
            }
        });
    });

    // Animação no header on scroll
    const header = document.querySelector('.header');
    window.addEventListener('scroll', () => {
        if (window.scrollY > 50) {
            header.style.boxShadow = "0 10px 20px rgba(0,0,0,0.08)";
            header.style.padding = "10px 0";
        } else {
            header.style.boxShadow = "none";
            header.style.padding = "15px 0";
        }
    });

    // Simulação dos dots no slider
    const dots = document.querySelectorAll('.dot');
    dots.forEach(dot => {
        dot.addEventListener('click', function() {
            const parent = this.parentElement;
            parent.querySelectorAll('.dot').forEach(d => {
                if (d.classList.contains('active')) {
                    d.classList.remove('active');
                }
                if (d.classList.contains('dark')) {
                    d.classList.remove('dark');
                }
            });
            this.classList.add('active');
            
            // Logica simulada para os dots adjacentes
            const allDots = Array.from(parent.querySelectorAll('.dot'));
            const index = allDots.indexOf(this);
            if (index > 0) allDots[index - 1].classList.add('dark');
            else if (allDots.length > 1) allDots[1].classList.add('dark');
        });
    });
    
    // Intersection Observer para as Animações "Fade", fazendo a UI super dinâmica
    const observerOptions = {
        root: null,
        rootMargin: '0px',
        threshold: 0.15 // Dispara quando 15% do componente está visível
    };

    const observer = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                // Opcionalmente podemos parar de observar após a primeira vez:
                // observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    const animatedElements = document.querySelectorAll('.animate-on-scroll');
    animatedElements.forEach(el => observer.observe(el));
});
