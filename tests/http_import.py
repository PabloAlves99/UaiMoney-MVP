"""Run against the disposable server created from tests/fixture.php."""
import http.cookiejar
import re
import secrets
import urllib.parse
import urllib.request

base = 'http://127.0.0.1:8787'
opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))

def get(path):
    response = opener.open(base + path)
    return response.read().decode('utf-8')

def post(path, values):
    response = opener.open(base + path, urllib.parse.urlencode(values).encode())
    return response.read().decode('utf-8')

def token(html):
    return re.search(r'name="_token" value="([^"]+)"', html).group(1)

html = get('/login')
html = post('/login', {'_token': token(html), 'identifier': 'ana', 'senha': 'UaiTeste!2026'})
assert 'Visão geral' in html
html = get('/movimentacoes/importar')
boundary = '----UaiMoney' + secrets.token_hex(8)
csv = 'Data;Descrição;Valor\n14/04/2025;Teste HTTP importação;-9,50\n'
parts = [
    f'--{boundary}\r\nContent-Disposition: form-data; name="_token"\r\n\r\n{token(html)}\r\n',
    f'--{boundary}\r\nContent-Disposition: form-data; name="conta_id"\r\n\r\n1\r\n',
    f'--{boundary}\r\nContent-Disposition: form-data; name="tipo_padrao"\r\n\r\noutro\r\n',
    f'--{boundary}\r\nContent-Disposition: form-data; name="arquivo"; filename="extrato.csv"\r\nContent-Type: text/csv\r\n\r\n{csv}\r\n',
    f'--{boundary}--\r\n',
]
request = urllib.request.Request(base + '/movimentacoes/importar/previa', ''.join(parts).encode(),
    headers={'Content-Type': 'multipart/form-data; boundary=' + boundary})
html = opener.open(request).read().decode('utf-8')
assert 'Teste HTTP importação' in html and 'Prévia salva' in html
nonce = re.search(r'name="nonce" value="([^"]+)"', html).group(1)
category = re.search(r'name="categoria\[0\]".*?<option value="(\d+)"', html, re.S).group(1)
html = post('/movimentacoes/importar', {'_token': token(html), 'nonce': nonce,
    'selecionar[0]': '1', 'categoria[0]': category})
assert 'Lançamento(s) importado(s)' in html
batch = re.search(r'name="lote_id" value="(\d+)"', html).group(1)
operation = re.findall(r'name="_operation" value="([^"]+)"', html)[-1]
html = post('/movimentacoes/importar/excluir', {'_token': token(html), '_operation': operation, 'lote_id': batch})
assert 'excluídos permanentemente' in html
print('OK: prévia, confirmação e exclusão permanente via HTTP.')
