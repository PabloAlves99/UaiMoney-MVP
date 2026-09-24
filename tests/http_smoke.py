"""HTTP smoke tests against the isolated preview started with tests/fixture.php."""
import http.cookiejar
import re
import secrets
import urllib.error
import urllib.parse
import urllib.request
from datetime import date
from html.parser import HTMLParser

BASE = 'http://127.0.0.1:8787'
checks = 0


def check(condition, message):
    global checks
    if not condition:
        raise AssertionError(message)
    checks += 1


class Forms(HTMLParser):
    def __init__(self, html):
        super().__init__()
        self.forms = []
        self.current = None
        self.feed(html)

    def handle_starttag(self, tag, attrs):
        attrs = dict(attrs)
        if tag == 'form':
            self.current = {'action': attrs.get('action', ''), 'fields': {}}
            self.forms.append(self.current)
        if tag == 'input' and self.current is not None and 'name' in attrs:
            self.current['fields'][attrs['name']] = attrs.get('value', '')

    def handle_endtag(self, tag):
        if tag == 'form':
            self.current = None


class Client:
    def __init__(self):
        self.opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))

    def request(self, path, values=None, method=None):
        data = None if values is None else urllib.parse.urlencode(values).encode()
        request = urllib.request.Request(BASE + path, data=data, method=method)
        try:
            response = self.opener.open(request)
        except urllib.error.HTTPError as error:
            response = error
        return response.status, response.read().decode('utf-8-sig'), response.headers

    def form(self, path, action):
        status, html, _ = self.request(path)
        check(status == 200, f'GET {path}')
        return next(f['fields'] for f in Forms(html).forms if f['action'] == action)

    def submit(self, path, action, values):
        fields = self.form(path, action)
        return self.request(action, fields | values)


ana = Client()
status, html, _ = ana.submit('/login', '/login', {'identifier': 'ana', 'senha': 'UaiTeste!2026'})
check(status == 200 and 'Visão geral' in html, 'Login')
paths = ['/', '/cartoes', '/cartoes/1', '/faturas/1', '/planejamento', '/analises', '/analises?agrupar=mes', '/carteira', '/comecar', '/movimentacoes', '/movimentacoes/1', '/movimentacoes/avancado', '/recorrencias', '/categorias', '/contas', '/contas/1', '/movimentacoes/5/editar']
for path in paths:
    status, html, headers = ana.request(path)
    check(status == 200, f'Route {path}: {status}')
    check('Fatal error' not in html and 'Warning:' not in html and 'Falha inesperada' not in html, f'Clean render {path}')
    check('no-store' in headers.get('Cache-Control', ''), f'No-cache {path}')

for path in ['/analises?periodo=anterior', '/analises?periodo=semestre', '/analises?inicio=2025-03-02&fim=2025-03-02&conferir=1', '/analises?inicio=2025-03-20&fim=2025-03-10', '/analises?tipo=receita&conferir=1', '/analises?conta_id=99999&conferir=1', '/analises?inicio=1900-01-01&fim=1900-01-31', '/analises?inicio=2199-12-01&fim=2199-12-31']:
    status, html, _ = ana.request(path)
    check(status == 200 and 'Warning:' not in html and 'Falha inesperada' not in html, f'Analytics state {path}')
status, html, _ = ana.request('/analises?inicio=2025-03-02&fim=2025-03-02&conferir=1')
check('Estorno · Mercado' in html and '- R$ 5,00' in html, 'Audit renders dated refund')
check('Distribuição do período' not in html and 'Maiores despesas' not in html, 'Analytics avoids duplicate breakdowns')

status, _, _ = ana.request('/nao-existe')
check(status == 404, '404')
status, _, headers = ana.request('/planejamento', method='PUT')
check(status == 405 and 'GET' in headers['Allow'], '405')
status, _, _ = ana.request('/cartoes', {'nome': 'Sem token'})
check(status == 419, 'CSRF guard')

new = Client()
suffix = secrets.token_hex(5)
status, html, _ = new.submit('/criar-conta', '/criar-conta', {'nome': 'Teste ' + suffix, 'login': 'teste' + suffix, 'email': suffix + '@example.test', 'senha': 'TesteNovo!2026', 'confirmacao_senha': 'TesteNovo!2026'})
check(status == 200 and 'Seu começo, passo a passo' in html, 'Public registration and onboarding')
status, html, _ = new.request('/')
check(status == 200 and 'Visão geral' in html and 'Vamos organizar seu começo?' in html, 'Empty dashboard')
for path in ['/cartoes/1', '/faturas/1', '/movimentacoes/1']:
    status, _, _ = new.request(path)
    check(status == 404, f'Foreign record blocked: {path}')
status, html, _ = new.submit('/comecar', '/comecar/categorias', {})
check(status == 200 and 'Categorias sugeridas adicionadas' in html, 'Seed categories')
status, html, _ = new.request('/movimentacoes')
check('0 lançamentos' in html and 'Notebook' not in html, 'Isolated transaction list')

status, html, _ = ana.submit('/cartoes', '/cartoes', {'nome': 'Cartão teste ' + suffix, 'limite': '1500', 'dia_fechamento': '20', 'dia_vencimento': '5', 'conta_pagamento_id': '1'})
check(status == 200 and 'Cartão salvo' in html, 'Create card')
card_id = re.search(r'/cartoes/(\d+)">Cartão teste ' + suffix, html).group(1)
status, html, _ = ana.request('/movimentacoes/nova?cartao_id=' + card_id)
category = re.search(r'<option value="(\d+)" data-type="despesa"', html).group(1)
payload = {'cartao_id': card_id, 'subgrupo_id': category, 'descricao': 'Compra HTTP ' + suffix, 'valor': '90.01', 'data': date.today().isoformat(), 'parcelas': '3', 'tipo': 'despesa', 'modo': 'parcelado', 'meio_pagamento': 'credito'}
fields = ana.form('/movimentacoes/nova', '/movimentacoes')
status, html, _ = ana.request('/movimentacoes', fields | payload)
check(status == 200 and 'Lançamento salvo' in html, 'Card purchase')
status, html, _ = ana.request('/movimentacoes', fields | payload)
check(status == 200, 'Replay purchase')
status, html, _ = ana.request('/movimentacoes?q=Compra+HTTP+' + suffix)
check('3 lançamentos' in html, 'Replay did not duplicate installments')

status, html, _ = ana.request('/cartoes/' + card_id)
invoice = re.findall(r'/faturas/(\d+)', html)[-1]
status, html, _ = ana.submit('/faturas/' + invoice, '/faturas/' + invoice + '/pagar', {'conta_id': '1', 'valor': '10', 'data': date.today().isoformat()})
check(status == 200 and 'Pagamento registrado' in html, 'Partial payment')
status, html, _ = new.submit('/cartoes', '/cartoes', {'nome': 'Ataque', 'id': card_id, 'limite': '10', 'dia_fechamento': '20', 'dia_vencimento': '5'})
check('Cartão não encontrado' in html, 'Foreign write blocked')

status, csv, headers = ana.request('/movimentacoes/exportar?q=Compra+HTTP+' + suffix)
check(status == 200 and 'text/csv' in headers['Content-Type'], 'CSV type')
check(csv.count('Compra HTTP ' + suffix) == 3, 'Filtered CSV')
status, csv, _ = new.request('/movimentacoes/exportar')
check('Compra HTTP' not in csv, 'CSV isolation')
fields = ana.form('/movimentacoes/nova', '/movimentacoes')
regular = {'tipo': 'despesa', 'data': date.today().isoformat(), 'descricao': '=SUM(1;2) ' + suffix, 'subgrupo_id': category, 'conta_id': '1', 'valor': '12.34', 'data_competencia': date.today().isoformat(), 'data_vencimento': date.today().isoformat(), 'status': 'pendente', 'meio_pagamento': 'pix'}
ana.request('/movimentacoes', fields | regular)
ana.request('/movimentacoes', fields | regular)
status, html, _ = ana.request('/movimentacoes?q=' + urllib.parse.quote(regular['descricao']))
check(status == 200 and '1 lançamento' in html, 'Cash form double-submit protection')
status, csv, _ = ana.request('/movimentacoes/exportar?q=' + urllib.parse.quote(regular['descricao']))
check("'=SUM" in csv, 'CSV formula injection escaped')
# Exercise the movement lifecycle through the actual forms and redirects.
query = '/movimentacoes?q=' + urllib.parse.quote(regular['descricao'])
status, html, _ = ana.request(query)
movement_id = re.search(r'id="movimento-(\d+)"', html).group(1)
detail = '/movimentacoes/' + movement_id
edit_path = detail + '/editar'
fields = ana.form(edit_path, edit_path)
changes = {'tipo': 'despesa', 'subgrupo_id': category, 'conta_id': '1', 'descricao': regular['descricao'], 'valor': '15.67', 'status': 'efetivada', 'meio_pagamento': 'pix', 'data_efetivacao': date.today().isoformat()}
status, html, _ = ana.request(edit_path, fields | changes)
check(status == 200 and 'Movimentação atualizada.' in html, 'Edit and mark paid through form')
status, html, _ = ana.request(detail)
check(status == 200 and '15,67' in html and 'Realizada' in html, 'Updated detail renders amount and status')
status, html, _ = ana.request(edit_path, fields | changes | {'_operation': secrets.token_hex(16)})
check(status == 422 and 'mudou desde' in html, 'Stale edit rejected through HTTP')
status, _, _ = new.request(edit_path)
check(status == 404, 'Foreign edit form blocked')
status, html, _ = ana.submit(detail, detail + '/excluir', {})
check(status == 200 and 'movida para a lixeira' in html, 'Delete through form')
status, _, _ = ana.request(detail)
check(status == 404, 'Deleted detail unavailable')
status, html, _ = ana.request(query)
check('0 lançamentos' in html, 'Deleted movement absent from active list')
trash = query + '&lixeira=1'
status, html, _ = ana.request(trash)
check(status == 200 and '1 lançamento' in html, 'Deleted movement visible in trash')
status, html, _ = ana.submit(trash, detail + '/restaurar', {})
check(status == 200 and 'Movimentação restaurada.' in html, 'Restore through form')
status, html, _ = ana.request(detail)
check(status == 200 and '15,67' in html and 'Restauração' in html, 'Restored detail and history')

print(f'OK: {checks} verificações HTTP, cadastro, formulários, CSRF, CSV e isolamento.')
