#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Build download page HTML (pages#5) from structured locale body — EN canonical layout."""

from __future__ import annotations

import html


def _e(text: str) -> str:
    return html.escape(text, quote=False)


def _cell(lang: str, cell: str | list) -> str:
    if isinstance(cell, str):
        return _e(cell)
    out = ""
    for part in cell:
        if isinstance(part, tuple):
            out += _link(lang, part[0], part[1])
        else:
            out += _e(part)
    return out


def _link(lang: str, path: str, text: str) -> str:
    href = path if path.startswith("/") else f"/{lang}/{path.lstrip('/')}"
    if not href.startswith(f"/{lang}"):
        if href == "/demo/":
            href = f"/{lang}/demo/"
        elif href.startswith("/download/") or href.startswith("/games/") or href.startswith("/casinos/"):
            href = f"/{lang}{href}"
    return f'<noads><a href="{href}">{_e(text)}</a></noads>'


def _fig(src: str, alt: str) -> str:
    return (
        f'<figure class="section-media__figure">'
        f'<img src="{src}" border="0" alt="{_e(alt)}" /></figure>'
    )


def build_download_content(body: dict, lang: str) -> str:
    """Render full pages#5 HTML. `body` keys match chickenroad_download_v2_en.get_en_body()."""
    b = body
    L = lang

    intro_p1 = (
        f"<p>{b['intro_p1_a']}{_link(L, '/demo/', b['lnk_demo'])}{b['intro_p1_b']}</p>"
    )
    intro_p2 = (
        f"<p>{_link(L, '/', b['lnk_cr'])}{b['intro_p2_b']}</p>"
    )
    intro_p3 = (
        f"<p>{_link(L, '/games/inout-games/', 'InOut Games')}{b['intro_p3_b']}</p>"
    )

    what_p1 = (
        f"<p>{b['what_p1_a']}{_link(L, '/games/inout-games/', 'InOut Games')}{b['what_p1_b']}</p>"
    )

    spec_rows = "".join(
        f"<tr><td>{_e(a)}</td><td>{_e(v) if not isinstance(v, tuple) else _link(L, v[0], v[1])}</td></tr>"
        for a, v in b["spec_rows"]
    )

    req_rows = "".join(
        f"<tr><td>{_e(a)}</td><td>{_cell(L, c)}</td><td>{_cell(L, d)}</td></tr>"
        for a, c, d in b["req_rows"]
    )

    diff_rows = "".join(
        f"<tr><td>{_e(a)}</td><td>{_e(c)}</td><td>{_e(d)}</td><td>{_e(e)}</td></tr>"
        for a, c, d, e in b["diff_rows"]
    )

    ol_items = "".join(f"<li>{item(L, b) if callable(item) else _e(item)}</li>" for item in b["download_ol"])

    ul_items = "".join(f"<li>{_e(item)}</li>" for item in b["download_ul"])

    faq_items = []
    for q, ans_parts in b["faq"]:
        ans = ""
        for part in ans_parts:
            if isinstance(part, tuple):
                ans += _link(L, part[0], part[1])
            else:
                ans += _e(part)
        faq_items.append(
            f'<details class="faq-item"><summary>{_e(q)}</summary>\n<p>{ans}</p>\n</details>'
        )
    faq_html = " ".join(faq_items)

    road2_p = (
        f"<p>{b['road2_p_a']}<strong>{_e(b['road2_strong'])}</strong>{b['road2_p_b']}"
        f"{_link(L, '/games/chicken-road/', b['lnk_cr_orig'])}"
        f"{b['road2_p_c']}"
        f"{_link(L, '/games/chicken-road2/', b['lnk_cr2'])}"
        f"{b['road2_p_d']}</p>"
    )

    use_p3 = (
        f"<p>{b['use_p3_a']}{_link(L, '/demo/', b['lnk_demo_guide'])}{b['use_p3_b']}</p>"
    )

    download_p_casino = (
        f"<p>{b['download_p_casino_a']}{_link(L, '/casinos/', b['lnk_casinos'])}{b['download_p_casino_b']}</p>"
    )

    return f"""<h1>{_e(b['h1'])}</h1>
<section id="download-intro" class="mt-5 pt-5">
<div class="container">
<div class="col-12">
<div class="main_heading">
<h2>{_e(b['h2_intro'])}</h2>
</div>
</div>
<div class="col-12">
<div class="about_content">
{_fig('/assets/images/chickenroad-download-hero.webp', b['alt_hero'])}
{intro_p1}
{intro_p2}
{intro_p3}
{_fig('/assets/images/chickenroad-app-desktop-mobile.webp', b['alt_app'])}
<p>{_e(b['intro_p4'])}</p>
<p>{_e(b['intro_p5'])}</p>
</div>
</div>
</div>
</section>
<section id="what-is-app" class="mt-5 pt-5">
<div class="container">
<div class="col-12">
<div class="main_heading">
<h2>{_e(b['h2_what'])}</h2>
</div>
</div>
<div class="row mt-4">
<div class="col-12">
<div class="about_content">
{what_p1}
<p>{_e(b['what_p2'])}</p>
<p>{_e(b['what_p3'])}</p>
<p>{_e(b['what_p4'])}</p>
</div>
</div>
<div class="row mt-4">
<div class="col-12">
<div class="table-responsive">
<table class="table table-bordered">
<thead>
<tr>
<th>{_e(b['th_spec_1'])}</th>
<th>{_e(b['th_spec_2'])}</th>
</tr>
</thead>
<tbody>
{spec_rows}
</tbody>
</table>
</div>
</div>
</div>
</div>
</div>
</section>
<section id="original-vs-road2" class="mt-5 pt-5">
<div class="container">
<div class="col-12">
<div class="main_heading">
<h2>{_e(b['h2_road2'])}</h2>
</div>
</div>
<div class="row mt-4">
<div class="col-12">
<div class="about_content">
{road2_p}
</div>
</div>
</div>
</div>
</section>
<section id="system-requirements" class="mt-5 pt-5">
<div class="container">
<div class="col-12">
<div class="main_heading">
<h2>{_e(b['h2_req'])}</h2>
</div>
</div>
<div class="row mt-4 align-items-start g-4">
<div class="col-xl-6 col-lg-6 col-md-12">
<div class="table-responsive">
<table class="table table-bordered">
<thead>
<tr>
<th>{_e(b['th_req_1'])}</th>
<th>{_e(b['th_req_2'])}</th>
<th>{_e(b['th_req_3'])}</th>
</tr>
</thead>
<tbody>
{req_rows}
</tbody>
</table>
</div>
</div>
<div class="col-xl-6 col-lg-6 col-md-12">
<div class="about_content section-media">
{_fig('/assets/images/chickenroad-mobile.webp', b['alt_mobile'])}
</div>
</div>
</div>
</div>
</section>
<section id="how-to-download" class="mt-5 pt-5">
<div class="container">
<div class="col-12">
<div class="main_heading">
<h2>{_e(b['h2_download'])}</h2>
</div>
</div>
<div class="row mt-4">
<div class="col-12">
<div class="about_content">
<ol>
{ol_items}
</ol>
<p><strong>{_e(b['download_why_h'])}</strong></p>
<ul>
{ul_items}
</ul>
{download_p_casino}
</div>
</div>
</div>
</div>
</section>
<section id="platform-differences" class="mt-5 pt-5">
<div class="container">
<div class="col-12">
<div class="main_heading">
<h2>{_e(b['h2_diff'])}</h2>
</div>
</div>
<div class="row mt-4 align-items-start g-4">
<div class="col-xl-7 col-lg-7 col-md-12">
<div class="table-responsive">
<table class="table table-bordered">
<thead>
<tr>
<th>{_e(b['th_diff_1'])}</th>
<th>{_e(b['th_diff_2'])}</th>
<th>{_e(b['th_diff_3'])}</th>
<th>{_e(b['th_diff_4'])}</th>
</tr>
</thead>
<tbody>
{diff_rows}
</tbody>
</table>
</div>
</div>
<div class="col-xl-5 col-lg-5 col-md-12">
<div class="about_content section-media">
{_fig('/assets/images/chickenroad-download-interface.webp', b['alt_interface'])}
</div>
</div>
</div>
</div>
</section>
<section id="how-to-use" class="mt-5 pt-5">
<div class="container">
<div class="col-12">
<div class="main_heading">
<h2>{_e(b['h2_use'])}</h2>
</div>
</div>
<div class="row mt-4 align-items-start g-4">
<div class="col-xl-6 col-lg-6 col-md-12">
<div class="about_content">
<p>{_e(b['use_p1'])}</p>
<p>{_e(b['use_p2'])}</p>
{use_p3}
</div>
</div>
<div class="col-xl-6 col-lg-6 col-md-12">
<div class="about_content section-media">
{_fig('/assets/images/chickenroad-gameplay.webp', b['alt_gameplay'])}
</div>
</div>
</div>
</div>
</section>
<section id="faq" class="mt-5 pt-5">
<div class="container">
<div class="col-12">
<div class="main_heading">
<h2>{_e(b['h2_faq'])}</h2>
</div>
</div>
<div class="row mt-3">
<div class="col-12">
<div class="faq-list">{faq_html}</div>
</div>
</div>
</div>
</section>"""
