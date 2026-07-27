from playwright.sync_api import sync_playwright
import time
import sys
import json
import os

sys.stdout.reconfigure(encoding='utf-8')

# Read credentials from demo-credentials.json
creds_path = os.path.join(os.path.dirname(__file__), 'storage', 'app', 'private', 'demo-credentials.json')
with open(creds_path, 'r') as f:
    creds = json.load(f)
rep = next(c for c in creds if c['role'] == 'rep')

with sync_playwright() as p:
    browser = p.chromium.launch(headless=True)
    context = browser.new_context(viewport={"width": 390, "height": 844})
    page = context.new_page()

    # Login
    page.goto('http://localhost:8000/login')
    page.wait_for_load_state('networkidle')
    time.sleep(2)

    page.evaluate('''(args) => {
        document.querySelector('input[type="email"]').value = args.email;
        document.querySelector('input[type="password"]').value = args.password;
        document.querySelector('input[type="email"]').dispatchEvent(new Event('input', { bubbles: true }));
        document.querySelector('input[type="password"]').dispatchEvent(new Event('input', { bubbles: true }));
    }''', {"email": rep['email'], "password": rep['password']})
    time.sleep(0.5)
    page.locator('button[type="submit"]').click()
    page.wait_for_url('**/app**', timeout=15000)
    time.sleep(2)

    print("=== Motion Verification ===")

    # 1. Check CSS animations are defined
    animations = page.evaluate('''() => {
        const sheets = document.styleSheets;
        const rules = [];
        for (const sheet of sheets) {
            try {
                for (const rule of sheet.cssRules) {
                    if (rule.type === CSSRule.KEYFRAMES_RULE) {
                        rules.push(rule.name);
                    }
                }
            } catch(e) {}
        }
        return rules;
    }''')
    print(f"1. Keyframe animations found: {animations}")

    # 2. Check home-section has animation
    section_anim = page.evaluate('''() => {
        const sections = document.querySelectorAll('.home-section');
        const results = [];
        sections.forEach((s, i) => {
            const style = getComputedStyle(s);
            results.push({
                index: i,
                animation: style.animation || style.animationName,
                opacity: style.opacity
            });
        });
        return results;
    }''')
    print(f"2. Home section animations: {section_anim}")

    # 3. Check tap feedback CSS is defined
    tap_feedback = page.evaluate('''() => {
        const pill = document.querySelector('.quick-action-pill');
        if (!pill) return 'no pill found';
        const style = getComputedStyle(pill);
        return {
            transition: style.transition,
            cursor: style.cursor
        };
    }''')
    print(f"3. Pill button tap feedback: {tap_feedback}")

    # 4. Check prefers-reduced-motion is defined
    reduced_motion = page.evaluate('''() => {
        const sheets = document.styleSheets;
        for (const sheet of sheets) {
            try {
                for (const rule of sheet.cssRules) {
                    if (rule.type === CSSRule.MEDIA_RULE && rule.conditionText && rule.conditionText.includes('prefers-reduced-motion')) {
                        return {
                            found: true,
                            condition: rule.conditionText,
                            rulesCount: rule.cssRules.length
                        };
                    }
                }
            } catch(e) {}
        }
        return { found: false };
    }''')
    print(f"4. Reduced motion support: {reduced_motion}")

    # 5. Test tap interaction - capture before/after
    pill = page.locator('.quick-action-pill').first
    if pill.count() > 0:
        pill.screenshot(path='C:/projects/jawla/screenshots/pill_before.png')
        pill.dispatch_event('pointerdown')
        time.sleep(0.05)
        pill.screenshot(path='C:/projects/jawla/screenshots/pill_during.png')
        pill.dispatch_event('pointerup')
        time.sleep(0.2)
        pill.screenshot(path='C:/projects/jawla/screenshots/pill_after.png')
        print("5. Pill tap screenshots captured")
    else:
        print("5. No pill button found")

    # 6. Verify skeleton loader animation
    skeleton_anim = page.evaluate('''() => {
        const skeleton = document.querySelector('.skeleton');
        if (!skeleton) return 'no skeleton on page (expected - we removed them from home)';
        const style = getComputedStyle(skeleton);
        return {
            animation: style.animation,
            background: style.background
        };
    }''')
    print(f"6. Skeleton animation: {skeleton_anim}")

    # 7. Check tab bar transition
    tab_transition = page.evaluate('''() => {
        const tab = document.querySelector('.tab-item');
        if (!tab) return 'no tab found';
        return getComputedStyle(tab).transition;
    }''')
    print(f"7. Tab bar transition: {tab_transition}")

    # 8. Verify motion CSS rules count
    motion_rules = page.evaluate('''() => {
        const sheets = document.styleSheets;
        let count = 0;
        for (const sheet of sheets) {
            try {
                for (const rule of sheet.cssRules) {
                    const text = rule.cssText || '';
                    if (text.includes('transition') || text.includes('animation') || text.includes('transform')) {
                        count++;
                    }
                }
            } catch(e) {}
        }
        return count;
    }''')
    print(f"8. Total motion-related CSS rules: {motion_rules}")

    browser.close()
    print("\n=== Motion verification complete ===")
