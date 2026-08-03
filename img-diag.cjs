const { chromium } = require('playwright');
(async () => {
  const b = await chromium.launch();
  for (const url of ['https://www.kalynyuk.com/', 'http://kalynyuk.loc/']) {
    const p = await b.newPage({ viewport: { width: 1440, height: 1200 } });
    await p.goto(url, { waitUntil: 'networkidle', timeout: 90000 });
    const r = await p.evaluate(() => {
      const img = document.querySelector('.about__portrait-img');
      const port = document.querySelector('.about__portrait');
      return {
        outerHTML: port ? port.innerHTML.replace(/\s+/g, ' ').trim().slice(0, 320) : null,
        attrW: img?.getAttribute('width'), attrH: img?.getAttribute('height'),
        natural: img ? [img.naturalWidth, img.naturalHeight] : null,
        currentSrc: img?.currentSrc,
        rendered: img ? [Math.round(img.getBoundingClientRect().width), Math.round(img.getBoundingClientRect().height)] : null,
      };
    });
    console.log('\n===', url, '===\n', JSON.stringify(r, null, 1));
    await p.close();
  }
  await b.close();
})();
