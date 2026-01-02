from playwright.sync_api import sync_playwright
import os

def run():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()

        # Load the test file
        url = f"file://{os.getcwd()}/verification/test.html"
        page.goto(url)

        # Wait for render
        page.wait_for_selector(".bsc-step-wrapper")

        # 1. Check Initial State (Mash)
        # Selectors inside the first step (index 0)
        # We need to look for inputs with correct values or classes
        # .bsc-metric-input is used for both duration and temp

        print("Checking initial MASH state...")
        # Since 'Mash' has both Duration and Temp, we should see metrics
        metrics = page.locator('.bsc-step-wrapper[data-index="0"] .bsc-metric-input')
        count = metrics.count()
        print(f"Metrics inputs found: {count} (Expected 2)")

        if count != 2:
            print("FAILURE: Expected 2 metric inputs for Mash")
        else:
            print("SUCCESS: Mash metrics visible")

        # 2. Change Type to PREP
        print("Changing type to PREP...")
        select = page.locator('.bsc-step-wrapper[data-index="0"] .bsc-step-type-select')
        select.select_option('prep')

        # Changing select triggers 'onchange' -> 'render()' which destroys DOM.
        # We must re-query.

        # 3. Check PREP State (Should have 0 metrics)
        metrics_after = page.locator('.bsc-step-wrapper[data-index="0"] .bsc-metric-input')
        count_after = metrics_after.count()
        print(f"Metrics inputs found: {count_after} (Expected 0)")

        if count_after != 0:
            print("FAILURE: Metrics still visible for PREP")
        else:
            print("SUCCESS: PREP metrics hidden")

        # 4. Interact with other fields (Comment, File)
        print("Testing file input and comment...")
        comment_area = page.locator('.bsc-step-wrapper[data-index="0"] .bsc-step-comment')
        comment_area.fill("Test comment")

        file_input = page.locator('.bsc-step-wrapper[data-index="0"] input[type="file"]')
        if file_input.is_visible():
            print("SUCCESS: File input is visible")
        else:
            print("FAILURE: File input missing")

        page.screenshot(path="verification/verification_conditional.png", full_page=True)
        browser.close()

if __name__ == "__main__":
    run()