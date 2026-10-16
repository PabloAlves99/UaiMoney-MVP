"""HTTP check for the saved import preview using tests/fixture.php."""
import http.cookiejar
import re
import secrets
import urllib.parse
import urllib.request
from html.parser import HTMLParser

BASE = "http://127.0.0.1:8787"


class Forms(HTMLParser):
    def __init__(self, html):
        super().__init__()
        self.forms = []
        self.current = None
        self.feed(html)

    def handle_starttag(self, tag, attrs):
        attrs = dict(attrs)
        if tag == "form":
            self.current = {"action": attrs.get("action", ""), "fields": {}}
            self.forms.append(self.current)
        elif tag == "input" and self.current is not None and "name" in attrs:
            self.current["fields"][attrs["name"]] = attrs.get("value", "")

    def handle_endtag(self, tag):
        if tag == "form":
            self.current = None


opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))


def request(path, fields=None, content_type=None):
    data = None if fields is None else fields if isinstance(fields, bytes) else urllib.parse.urlencode(fields).encode()
    headers = {"Content-Type": content_type} if content_type else {}
    response = opener.open(urllib.request.Request(BASE + path, data=data, headers=headers))
    return response.read().decode("utf-8-sig")


def form_fields(html, action):
    return next(item["fields"] for item in Forms(html).forms if item["action"] == action)


login = request("/login")
request("/login", form_fields(login, "/login") | {"identifier": "ana", "senha": "UaiTeste!2026"})
page = request("/movimentacoes/importar")
fields = form_fields(page, "/movimentacoes/importar/previa")
fields["conta_id"] = "1"
boundary = "----UaiMoney" + secrets.token_hex(12)
parts = []
for name, value in fields.items():
    parts.append(f"--{boundary}\r\nContent-Disposition: form-data; name=\"{name}\"\r\n\r\n{value}\r\n".encode())
parts.append(f"--{boundary}\r\nContent-Disposition: form-data; name=\"arquivo\"; filename=\"teste.csv\"\r\nContent-Type: text/csv\r\n\r\n".encode())
parts.append("Data;Descrição;Valor\n01/05/2025;Rascunho primeira;-8,00\n02/05/2025;Rascunho segunda;-9,00\n".encode())
parts.append(f"\r\n--{boundary}--\r\n".encode())
page = request("/movimentacoes/importar/previa", b"".join(parts), f"multipart/form-data; boundary={boundary}")
assert "2 linhas pendentes" in page and "Rascunho primeira" in page
form = form_fields(page, "/movimentacoes/importar")
category_select = re.search(r'<select[^>]*name="categoria\[0\]"[^>]*>(.*?)</select>', page, re.S).group(1)
category = re.search(r'<option value="(\d+)"', category_select).group(1)
request("/movimentacoes/importar/rascunho", form | {"selecionar[0]": "1", "categoria[0]": category})
request("/categorias")
page = request("/movimentacoes/importar")
assert "2 linhas pendentes" in page and f'name="selecionar[0]" value="1" checked' in page
form = form_fields(page, "/movimentacoes/importar")
page = request("/movimentacoes/importar", form | {"somente": "0", "categoria[0]": category})
assert "1 linha pendente" in page and "Rascunho segunda" in page and "Rascunho primeira" not in page
form = form_fields(page, "/movimentacoes/importar")
page = request("/movimentacoes/importar/linha/remover", form | {"indice": "0"})
assert "Prévia salva" not in page and "Última importação" in page
print("OK: prévia persistente, envio de uma linha e remoção via HTTP.")
