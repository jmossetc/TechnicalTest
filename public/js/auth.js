const AUTH_KEY = 'shm_token';

function getToken() {
    return localStorage.getItem(AUTH_KEY);
}

function setToken(token) {
    localStorage.setItem(AUTH_KEY, token);
}

function clearToken() {
    localStorage.removeItem(AUTH_KEY);
}

function getEmail() {
    const token = getToken();
    if (!token) return '';
    try {
        const payload = JSON.parse(atob(token.split('.')[1]));
        return payload.email || payload.sub || 'User';
    } catch {
        return 'User';
    }
}
