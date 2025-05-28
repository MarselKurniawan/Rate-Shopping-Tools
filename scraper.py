# scraper.py
import json
import mysql.connector
from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from datetime import datetime, timedelta

def create_driver():
    from selenium.webdriver.chrome.service import Service
    options = Options()
    # options.add_argument("--headless=new")
    options.add_argument("--disable-gpu")
    options.add_argument("--no-sandbox")
    options.add_argument("--disable-dev-shm-usage")

    # GANTI path sesuai lokasi chromedriver.exe kamu
    chrome_path = "chromedriver.exe"
    service = Service(executable_path=chrome_path)

    return webdriver.Chrome(service=service, options=options)


def save_to_mysql(data):
    conn = mysql.connector.connect(
        host="localhost",
        user="root",
        password="",  # sesuaikan kalau kamu pake password
        database="hoteldata"
    )
    cursor = conn.cursor()
    for row in data:
        cursor.execute("""
            INSERT INTO harga_hotel (property_name, type, kompetitor_dari, tanggal, harga, ota)
            VALUES (%s, %s, %s, %s, %s, %s)
        """, (row["name"], row["type"], row.get("of"), row["date"], row["price"],row["ota"]))
    conn.commit()
    conn.close()

def scrape():
    with open("input.json") as f:
        hotel_data = json.load(f)

    driver = create_driver()
    wait = WebDriverWait(driver, 10)
    hasil = []

    for hotel in hotel_data:
        for i in range(30):
            tgl = datetime.today() + timedelta(days=i)
            tgl_str = tgl.strftime("%Y-%m-%d")

            url = f"https://www.agoda.com/{hotel['url']}.html?checkin={tgl_str}&los=1&adults=1&children=0&rooms=1&currencyCode=IDR"
            driver.get(url)

            try:
                price_elem = wait.until(EC.presence_of_element_located(
                    (By.CSS_SELECTOR, "span[data-selenium='PriceDisplay']")
                ))
                price = price_elem.text.strip()
            except:
                price = ""

            hasil.append({
                "name": hotel["name"],
                "type": hotel["type"],
                "of": hotel.get("of"),
                "date": tgl_str,
                "price": price,
                "ota": hotel["ota"]
            })
            print(f"{hotel['name']} - {tgl_str} : {price}")

    driver.quit()
    save_to_mysql(hasil)
    print("✅ Data berhasil disimpan ke MySQL.")

if __name__ == "__main__":
    scrape()
