from playwright.sync_api import sync_playwright
import time
import sys

sys.stdout.reconfigure(encoding='utf-8')

with sync_playwright() as p:
    browser = p.chromium.launch(headless=True)
    context = browser.new_context(viewport={"width": 390, "height": 844})
    page = context.new_page()
    
    # Login
    page.goto('http://localhost:8000/login')
    page.wait_for_load_state('networkidle')
    time.sleep(2)
    
    page.evaluate('''() => {
        document.querySelector('input[type="email"]').value = 'rep@jawla.test';
        document.querySelector('input[type="password"]').value = 'm[hK~;}VF4&cx{0#JNkqa*[M';
        document.querySelector('input[type="email"]').dispatchEvent(new Event('input', { bubbles: true }));
        document.querySelector('input[type="password"]').dispatchEvent(new Event('input', { bubbles: true }));
    }''')
    time.sleep(0.5)
    page.locator('button[type="submit"]').click()
    page.wait_for_url('**/app**', timeout=15000)
    time.sleep(2)
    
    # Screenshot home light
    page.screenshot(path='C:/projects/jawla/screenshots/02_home_light.png', full_page=True)
    print("Screenshot 02: Home page (light)")
    
    # Navigate to settings
    page.goto('http://localhost:8000/app/settings')
    page.wait_for_load_state('networkidle')
    time.sleep(2)
    page.screenshot(path='C:/projects/jawla/screenshots/03_settings_light.png', full_page=True)
    print("Screenshot 03: Settings (light)")
    
    # Try to click dark mode button
    try:
        # Look for button with text "داكن" or "Dark"
        dark_btn = page.locator('button:has-text("داكن")')
        if dark_btn.count() == 0:
            dark_btn = page.locator('button:has-text("Dark")')
        if dark_btn.count() > 0:
            dark_btn.first.click()
            time.sleep(1)
            print("Clicked dark mode")
        else:
            print("Dark mode button not found")
            # Let's see what buttons exist
            buttons = page.locator('button').all()
            for b in buttons[:10]:
                txt = b.inner_text()
                print(f"  Button: {txt}")
    except Exception as e:
        print(f"Error clicking dark: {e}")
    
    page.screenshot(path='C:/projects/jawla/screenshots/04_settings_dark.png', full_page=True)
    print("Screenshot 04: Settings (dark)")
    
    # Navigate to home in dark mode
    page.goto('http://localhost:8000/app')
    page.wait_for_load_state('networkidle')
    time.sleep(2)
    page.screenshot(path='C:/projects/jawla/screenshots/05_home_dark.png', full_page=True)
    print("Screenshot 05: Home (dark)")
    
    browser.close()
    print("\nDone!")
