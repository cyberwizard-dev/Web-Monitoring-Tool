const puppeteer = require("puppeteer");
const path = require("path");

const url = process.argv[2];

if (!url) {
    console.error('No URL provided.');
    process.exit(1);
}

async function takeScreenshot() {
    const timestamp = new Date().toISOString().replace(/[:.-]/g, '_');
    const screenshotName = `screenshot_${timestamp}.png`;
    const screenshotPath = path.join(__dirname, "screenshots", screenshotName);

    const browser = await puppeteer.launch({
        defaultViewport: {
            width: 1280,
            height: 2000,
        },
    });

    const page = await browser.newPage();
    await page.goto(url);
    await page.screenshot({path: screenshotPath});
    await browser.close();

    console.log(screenshotName);
}

takeScreenshot().catch(console.error);
