const api = {
    async request(method, path, body = null, params = null) {
        const token = getToken();
        if (!token) { location.href = 'index.html'; return new Promise(() => {}); }

        let url = path;
        if (params) {
            const qs = new URLSearchParams(
                Object.fromEntries(
                    Object.entries(params).filter(([, v]) => v !== '' && v !== null && v !== undefined)
                )
            ).toString();
            if (qs) url += '?' + qs;
        }

        const headers = { 'Authorization': 'Bearer ' + token };
        if (body !== null) headers['Content-Type'] = 'application/json';

        let response;
        try {
            response = await fetch(url, {
                method,
                headers,
                body: body !== null ? JSON.stringify(body) : undefined,
            });
        } catch {
            throw { error: 'Network error. Is the server running?' };
        }

        if (response.status === 401) {
            clearToken();
            location.href = 'index.html';
            return new Promise(() => {});
        }

        let data;
        try { data = await response.json(); } catch { data = {}; }

        if (!response.ok) {
            throw { status: response.status, error: data.error || response.statusText };
        }

        return data;
    },

    get(path, params)  { return this.request('GET',    path, null, params); },
    post(path, body)   { return this.request('POST',   path, body); },
    patch(path, body)  { return this.request('PATCH',  path, body); },
    delete(path)       { return this.request('DELETE', path); },
};
