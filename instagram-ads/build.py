#!/usr/bin/env python3
"""Inject brand.json + base64 fonts into the HTML templates -> build/*.html"""
import base64, pathlib

root = pathlib.Path(__file__).parent
fonts = root / 'fonts'

def b64(name: str) -> str:
    return base64.b64encode((fonts / name).read_bytes()).decode()

fonts_css = f"""
@font-face{{font-family:'Vazirmatn';font-weight:400;font-display:block;src:url(data:font/woff2;base64,{b64('Vazirmatn-Regular.woff2')}) format('woff2');}}
@font-face{{font-family:'Vazirmatn';font-weight:700;font-display:block;src:url(data:font/woff2;base64,{b64('Vazirmatn-Bold.woff2')}) format('woff2');}}
@font-face{{font-family:'Inter';font-weight:400;font-display:block;src:url(data:font/woff2;base64,{b64('Inter-Regular.woff2')}) format('woff2');}}
@font-face{{font-family:'Inter';font-weight:700;font-display:block;src:url(data:font/woff2;base64,{b64('Inter-Bold.woff2')}) format('woff2');}}
"""

brand = (root / 'src' / 'brand.json').read_text().strip()

for name in ('post', 'story'):
    html = (root / 'src' / f'{name}.html').read_text()
    html = html.replace('@@FONTS@@', fonts_css).replace('@@BRAND@@', brand)
    out = root / 'build' / f'{name}.html'
    out.write_text(html)
    print(f'built build/{name}.html ({len(html)//1024} KB)')