#!/usr/bin/env python3
"""Generate PowerBall Jackpot logo + favicon (red lottery ball + white P)."""
from __future__ import annotations

import os

from PIL import Image, ImageDraw, ImageFont, ImageFilter

ROOT = os.path.join(os.path.dirname(__file__), '..')
ASSETS = os.path.join(ROOT, 'assets', 'images')

RED = (210, 24, 40)
RED_DARK = (140, 12, 24)
WHITE = (255, 255, 255)
GREEN_TEXT = (68, 140, 116)
RED_TEXT = (232, 69, 69)
TRANSPARENT = (0, 0, 0, 0)


def load_font(size: int) -> ImageFont.FreeTypeFont | ImageFont.ImageFont:
    for path in (
        '/System/Library/Fonts/Supplemental/Arial Black.ttf',
        '/System/Library/Fonts/Supplemental/Arial Bold.ttf',
        '/Library/Fonts/Arial Bold.ttf',
    ):
        if os.path.isfile(path):
            try:
                return ImageFont.truetype(path, size)
            except OSError:
                continue
    return ImageFont.load_default()


def centered_text(draw: ImageDraw.ImageDraw, box: tuple, text: str, font, fill, italic_deg: float = 0):
    x0, y0, x1, y1 = box
    bbox = draw.textbbox((0, 0), text, font=font)
    cx = (x0 + x1) / 2
    cy = (y0 + y1) / 2
    tx = cx - (bbox[2] - bbox[0]) / 2 - bbox[0]
    ty = cy - (bbox[3] - bbox[1]) / 2 - bbox[1]
    if abs(italic_deg) < 0.01:
        draw.text((tx, ty), text, fill=fill, font=font)
        return None
    tmp = Image.new('RGBA', (int(bbox[2] - bbox[0] + 16), int(bbox[3] - bbox[1] + 16)), TRANSPARENT)
    tdraw = ImageDraw.Draw(tmp)
    tdraw.text((8 - bbox[0], 8 - bbox[1]), text, fill=fill, font=font)
    tmp = tmp.rotate(italic_deg, expand=True, resample=Image.Resampling.BICUBIC)
    return tmp, (int(cx - tmp.width / 2), int(cy - tmp.height / 2))


def paste_letter(img: Image.Image, size: int, italic_deg: float = 0):
    font = load_font(int(size * 0.50))
    layer = Image.new('RGBA', (size, size), TRANSPARENT)
    d = ImageDraw.Draw(layer)
    result = centered_text(d, (0, 0, size, size), 'P', font, WHITE, italic_deg=italic_deg)
    if result:
        tmp, pos = result
        img.alpha_composite(tmp, dest=pos)
    return img


def draw_flat_ball(size: int) -> Image.Image:
    img = Image.new('RGBA', (size, size), TRANSPARENT)
    d = ImageDraw.Draw(img)
    pad = max(1, size // 28)
    d.ellipse([pad, pad, size - pad, size - pad], fill=RED)
    font = load_font(int(size * 0.58))
    centered_text(d, (0, 0, size, size), 'P', font, WHITE)
    return img


def draw_glossy_ball(size: int, letter_tilt: float = 0) -> Image.Image:
    img = Image.new('RGBA', (size, size), TRANSPARENT)
    pad = max(2, size // 24)
    outer = [pad, pad, size - pad - 1, size - pad - 1]

    layers = Image.new('RGBA', (size, size), TRANSPARENT)
    ld = ImageDraw.Draw(layers)
    cx = cy = size / 2
    r = (size - pad * 2) / 2
    for i in range(int(r), 0, -1):
        t = i / r
        color = (
            int(RED_DARK[0] + (RED[0] - RED_DARK[0]) * t),
            int(RED_DARK[1] + (RED[1] - RED_DARK[1]) * t),
            int(RED_DARK[2] + (RED[2] - RED_DARK[2]) * t),
            255,
        )
        ld.ellipse([cx - i, cy - i + i * 0.08, cx + i, cy + i + i * 0.08], fill=color)
    img = Image.alpha_composite(img, layers)

    d = ImageDraw.Draw(img)
    d.ellipse(outer, outline=(255, 255, 255, 35))

    highlight = Image.new('RGBA', (size, size), TRANSPARENT)
    hd = ImageDraw.Draw(highlight)
    span = size - pad * 2
    hd.ellipse(
        [
            pad + span * 0.18,
            pad + span * 0.12,
            pad + span * 0.60,
            pad + span * 0.40,
        ],
        fill=(255, 255, 255, 95),
    )
    highlight = highlight.filter(ImageFilter.GaussianBlur(radius=max(1, size // 40)))
    img = Image.alpha_composite(img, highlight)

    shadow = Image.new('RGBA', (size, size), TRANSPARENT)
    sd = ImageDraw.Draw(shadow)
    sd.ellipse(
        [
            pad + span * 0.15,
            size - pad - span * 0.35,
            size - pad - span * 0.15,
            size - pad - span * 0.05,
        ],
        fill=(0, 0, 0, 55),
    )
    shadow = shadow.filter(ImageFilter.GaussianBlur(radius=max(1, size // 36)))
    img = Image.alpha_composite(img, shadow)

    paste_letter(img, size, italic_deg=letter_tilt)
    return img


def draw_wordmark_logo(ball_size: int = 62) -> Image.Image:
    h = ball_size + 6
    ball = draw_glossy_ball(ball_size, letter_tilt=-10)
    f_main = load_font(int(h * 0.50))
    f_sub = load_font(int(h * 0.34))

    probe = Image.new('RGBA', (1, 1))
    pd = ImageDraw.Draw(probe)
    text_w = 12
    for text in ('POWER', 'BALL'):
        bb = pd.textbbox((0, 0), text, font=f_main)
        text_w += bb[2] - bb[0] + 8
    bb = pd.textbbox((0, 0), 'JACKPOT', font=f_sub)
    text_w += bb[2] - bb[0] + 8

    img = Image.new('RGBA', (ball_size + text_w, h), TRANSPARENT)
    img.paste(ball, (0, (h - ball_size) // 2), ball)

    d = ImageDraw.Draw(img)
    x = ball_size + 12
    for text, color in (('POWER', GREEN_TEXT), ('BALL', RED_TEXT)):
        d.text((x, h * 0.12), text, fill=color, font=f_main)
        bb = d.textbbox((x, h * 0.12), text, font=f_main)
        x = bb[2] + 8
    d.text((x + 4, h * 0.22), 'JACKPOT', fill=RED_TEXT, font=f_sub)
    return img


def write_svg(path: str):
    svg = '''<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 420 68" role="img" aria-label="PowerBall Jackpot">
  <defs>
    <radialGradient id="ball" cx="35%" cy="30%" r="65%">
      <stop offset="0%" stop-color="#ff5a66"/>
      <stop offset="55%" stop-color="#d21828"/>
      <stop offset="100%" stop-color="#8c0c18"/>
    </radialGradient>
  </defs>
  <circle cx="34" cy="34" r="30" fill="url(#ball)"/>
  <ellipse cx="24" cy="22" rx="11" ry="7" fill="#ffffff" opacity="0.35"/>
  <text x="34" y="44" text-anchor="middle" font-family="Arial Black, Arial, Helvetica, sans-serif" font-weight="900" font-size="32" fill="#ffffff" transform="rotate(-10 34 34)">P</text>
  <text x="78" y="42" font-family="Arial, Helvetica, sans-serif" font-weight="700" font-size="34" fill="#448C74">POWER</text>
  <text x="198" y="42" font-family="Arial, Helvetica, sans-serif" font-weight="700" font-size="34" fill="#E84545">BALL</text>
  <text x="292" y="38" font-family="Arial, Helvetica, sans-serif" font-weight="700" font-size="24" fill="#E84545">JACKPOT</text>
</svg>'''
    with open(path, 'w', encoding='utf-8') as f:
        f.write(svg)


def save(img: Image.Image, rel_path: str):
    path = os.path.join(ROOT, rel_path)
    os.makedirs(os.path.dirname(path), exist_ok=True)
    img.save(path, 'PNG', optimize=True)
    print(f'wrote {rel_path} {img.size}')


def main():
    save(draw_wordmark_logo(62), 'assets/images/logo.png')
    save(draw_flat_ball(64), 'assets/images/favicon.png')
    save(draw_glossy_ball(180), 'assets/images/pwa-icon-180.png')
    save(draw_glossy_ball(192), 'assets/images/pwa-icon-192.png')
    save(draw_glossy_ball(512), 'assets/images/pwa-icon-512.png')
    save(draw_glossy_ball(180), 'apple-touch-icon.png')
    write_svg(os.path.join(ASSETS, 'logo-powerball.svg'))
    print('done')


if __name__ == '__main__':
    main()
