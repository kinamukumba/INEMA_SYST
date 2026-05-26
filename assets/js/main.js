let scrollObserver;

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

    scrollObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                // Opcionalmente podemos parar de observar após a primeira vez:
                // observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    const animatedElements = document.querySelectorAll('.animate-on-scroll');
    animatedElements.forEach(el => scrollObserver.observe(el));

    loadActiveBases();
});

async function loadActiveBases() {
    const basesContainer = document.getElementById('basesGrid');
    if (!basesContainer) return;

    try {
        const res = await ApiClient.get('public.php?action=get_bases');
        if (!res.success || !Array.isArray(res.data) || res.data.length === 0) {
            basesContainer.innerHTML = `
                <div class="base-card card animate-on-scroll fade-up" style="transition-delay: 0.1s;">
                    <div class="base-card-header">
                        <div class="plus-icon"><i class="fa-solid fa-house-medical"></i></div>
                    </div>
                    <div class="base-card-body">
                        <h3 class="text-blue text-center">Nenhuma base activa</h3>
                        <p class="text-center">No momento não há bases ativas cadastradas.</p>
                    </div>
                </div>
            `;
            return;
        }

        basesContainer.innerHTML = res.data.map((base, index) => {
            const delay = (index + 1) * 0.1;
            const endereco = base.endereco ? base.endereco : 'Endereço não disponível';
            return `
                <div class="base-card card animate-on-scroll fade-up" style="transition-delay: ${delay}s;">
                    <div class="base-card-header">
                        <div class="plus-icon"><i class="fa-solid fa-plus"></i></div>
                    </div>
                    <div class="base-card-body">
                        <h3 class="text-blue text-center">${base.nome_base}</h3>
                        <p class="text-center">${base.municipio}</p>
                        <p class="text-center">${endereco}</p>
                        <p class="text-center" style="margin-top: 1rem; font-weight: 700;">Capacidade: ${base.capacidade}</p>
                        <div class="text-center mt-20">
                            <button class="btn btn-primary btn-sm btn-rounded-full text-blue">Ligar ou Dirigir</button>
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        // Re-observar os novos cards para animação on scroll
        const newCards = document.querySelectorAll('.animate-on-scroll');
        newCards.forEach(el => scrollObserver.observe(el));
    } catch (error) {
        console.error('Erro ao carregar bases:', error);
        basesContainer.innerHTML = `
            <div class="base-card card animate-on-scroll fade-up" style="transition-delay: 0.1s;">
                <div class="base-card-header">
                    <div class="plus-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
                </div>
                <div class="base-card-body">
                    <h3 class="text-blue text-center">Erro ao carregar bases</h3>
                    <p class="text-center">Verifique a ligação ou tente novamente mais tarde.</p>
                </div>
            </div>
        `;
    }
}
