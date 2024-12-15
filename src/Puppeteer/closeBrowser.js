const { Page, Browser } = require('puppeteer');
const puppeteer = require('puppeteer-extra');
const StealthPlugin = require('puppeteer-extra-plugin-stealth');
const { connectBrowserPage } = require('./connectBrowserPage');
puppeteer.use(StealthPlugin());

closeBrowser().then(() => process.exit(0));

async function closeBrowser() {
  const page = await connectBrowserPage();
  page.browser().close();
}
