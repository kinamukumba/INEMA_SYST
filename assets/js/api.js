const API_URL = 'http://localhost/tcc/backend/api/';

const ApiClient = {
    async post(endpoint, data) {
        try {
            const response = await fetch(`${API_URL}${endpoint}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            return await response.json();
        } catch (error) {
            console.error('Erro na requisição:', error);
            return { success: false, message: 'Erro de conexão com o servidor' };
        }
    },

    async get(endpoint) {
        try {
            const response = await fetch(`${API_URL}${endpoint}`);
            return await response.json();
        } catch (error) {
            console.error('Erro na requisição:', error);
            return { success: false, message: 'Erro de conexão com o servidor' };
        }
    }
};

// Carregar dados globais do usuário logado
async function loadUserData() {
    const res = await ApiClient.get('auth.php?action=get_user_data');
    if (res.success) {
        // Atualizar elementos da UI com dados reais
        const userElements = document.querySelectorAll('.user-name-placeholder');
        userElements.forEach(el => el.textContent = res.data.nome);
        return res.data;
    } else {
        // Se não estiver logado e estiver em uma página restrita, redirecionar
        if (!window.location.pathname.includes('login.html') && !window.location.pathname.includes('register.html') && window.location.pathname !== '/tcc/') {
            window.location.href = '/tcc/login.html';
        }
    }
}
