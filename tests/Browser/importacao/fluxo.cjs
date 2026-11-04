const { chromium } = require('playwright');
const assert = require('node:assert/strict');
const path = require('node:path');

(async () => {
    const browser = await chromium.launch({ headless: true });
    const page = await browser.newPage({ viewport: { width: 360, height: 800 } });
    page.on('pageerror', error => console.error('Erro no navegador:', error.message));
    try {
        await page.goto(`${process.env.BASE_URL}/login`);
        await page.locator('[name=email]').fill('teste@example.com');
        await page.locator('[name=senha]').fill('teste123');
        await page.locator('form button[type=submit]').click();
        await page.goto(`${process.env.BASE_URL}/spreadsheets/import`);
        await page.locator('[name=administracao_id]').selectOption('1');
        await page.locator('[name=arquivo_csv]').setInputFiles(path.join(__dirname, '../../Fixtures/importacao/relatorio-ficticio.xls'));
        await page.getByRole('button', { name: 'Enviar e analisar' }).click();
        await page.waitForURL(/\/spreadsheets\/preview\/\d+/, { timeout: 30000 });
        assert.match(await page.locator('body').innerText(), /99-9999/);
        let salvo = page.waitForResponse(response => response.url().endsWith('/actions') && response.request().method() === 'POST');
        if (process.env.INVESTIGAR_IMPORTAR_TUDO === '1') {
            await page.locator('.js-preview-church-action').selectOption('personalizado');
            assert.equal((await salvo).status(), 200);
            salvo = page.waitForResponse(response => response.url().endsWith('/actions') && response.request().method() === 'POST');
            await page.locator('.js-preview-church-action').selectOption('importar');
            const resposta = await salvo;
            assert.equal(resposta.status(), 200);
            assert.deepEqual(Object.values(resposta.request().postDataJSON().dependencias), ['importar']);
            console.log('POST actions:', JSON.stringify(resposta.request().postDataJSON()), 'HTTP', resposta.status(), JSON.stringify(await resposta.json()));
            salvo = page.waitForResponse(response => response.url().endsWith('/actions') && response.request().method() === 'POST');
            await page.locator('.js-preview-church-action').selectOption('personalizado');
            const personalizada = await salvo;
            assert.equal(personalizada.status(), 200);
            assert.deepEqual(Object.values(personalizada.request().postDataJSON().dependencias), ['pular']);
            salvo = page.waitForResponse(response => response.url().endsWith('/actions') && response.request().method() === 'POST');
            await page.locator('.js-preview-church-action').selectOption('importar');
            assert.equal((await salvo).status(), 200);
        } else {
            await page.locator('.js-preview-church-action').selectOption('personalizado');
            assert.equal((await salvo).status(), 200);
            salvo = page.waitForResponse(response => response.url().endsWith('/actions') && response.request().method() === 'POST');
            await page.locator('.js-preview-dependency-action').selectOption('importar');
            assert.equal((await salvo).status(), 200);
        }
        const confirmacao = page.waitForResponse(response => /\/confirm$/.test(new URL(response.url()).pathname) && response.request().method() === 'POST');
        const processamento = page.waitForResponse(response => /\/start$/.test(new URL(response.url()).pathname) && response.request().method() === 'POST');
        await page.getByRole('button', { name: 'Confirmar igrejas selecionadas' }).click();
        const confirmada = await confirmacao;
        if (process.env.INVESTIGAR_IMPORTAR_TUDO === '1') console.log('POST confirm: importar_tudo=', new URLSearchParams(confirmada.request().postData()).get('importar_tudo'), 'HTTP', confirmada.status());
        await page.waitForURL(/\/spreadsheets\/process\/\d+/);
        const processada = await processamento;
        if (process.env.INVESTIGAR_IMPORTAR_TUDO === '1') console.log('POST start: HTTP', processada.status(), JSON.stringify(await processada.json()));
        await page.locator('#sucesso-container').waitFor({ state: 'visible', timeout: 30000 });
        assert.equal(await page.locator('#sucesso-linhas').innerText(), '2');
        console.log('Fluxo concluído: 2 linhas importadas na tela de 360 px.');
    } catch (error) {
        console.error('URL:', page.url());
        throw error;
    } finally {
        await browser.close();
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
