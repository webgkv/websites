/**
 * E2E: mobile burger menu — click .menu-toggle opens .navbarNav (adds .active).
 * Serves tests/fixtures/burger.html (same DOM as site) with project assets.
 */
import { test, expect } from '@playwright/test';

const FIXTURE_URL = '/tests/fixtures/burger.html';
const MOBILE_VIEWPORT = { width: 430, height: 800 };

test.describe('Burger menu', () => {
  test.beforeEach(async ({ page }) => {
    await page.setViewportSize(MOBILE_VIEWPORT);
    await page.goto(FIXTURE_URL);
    await page.waitForSelector('.menu-toggle', { state: 'visible' });
  });

  test('click on burger adds .active to navbarNav and opens menu', async ({ page }) => {
    const nav = page.locator('#navbarNav');
    const burger = page.locator('.menu-toggle');

    await expect(nav).not.toHaveClass(/active/);

    await burger.click();

    await expect(nav).toHaveClass(/active/);
    const toggleClicks = await page.evaluate(() => window._burgerDebug?.toggleClicks ?? -1);
    expect(toggleClicks).toBe(1);
  });

  test('second click removes .active (menu closes)', async ({ page }) => {
    const nav = page.locator('#navbarNav');
    const burger = page.locator('.menu-toggle');

    await burger.click();
    await expect(nav).toHaveClass(/active/);

    await burger.click();
    await expect(nav).not.toHaveClass(/active/);
  });

  test('click on nav link closes menu', async ({ page }) => {
    const nav = page.locator('#navbarNav');
    const burger = page.locator('.menu-toggle');

    await burger.click();
    await expect(nav).toHaveClass(/active/);

    await page.locator('#navbarNav a.nav-link').first().click();
    await expect(nav).not.toHaveClass(/active/);
  });

  test('touchend path opens menu (siteBurgerTap)', async ({ page }) => {
    const nav = page.locator('#navbarNav');
    await expect(nav).not.toHaveClass(/active/);
    await page.evaluate(() => {
      if (window.siteBurgerTap) {
        var e = new Event('touchend', { bubbles: true, cancelable: true });
        window.siteBurgerTap(e);
      }
    });
    await expect(nav).toHaveClass(/active/);
    const touches = await page.evaluate(() => window._burgerDebug?.toggleTouches ?? -1);
    expect(touches).toBe(1);
  });
});
