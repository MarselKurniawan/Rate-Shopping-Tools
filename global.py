# scraper.py
import json
import time
import mysql.connector
from datetime import datetime, timedelta
from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC

def create_driver():
    options = Options()
    # options.add_argument("--headless=new")  # opsional
    options.add_argument("--disable-gpu")
    options.add_argument("--no-sandbox")
    options.add_argument("--disable-dev-shm-usage")
    service = Service(executable_path="chromedriver.exe")  # sesuaikan path
    return webdriver.Chrome(service=service, options=options)

def save_to_mysql(data):
    if not data:
        print("⛔ Tidak ada data yang disimpan.")
        return

    conn = mysql.connector.connect(
        host="localhost",
        user="root",
        password="",
        database="hoteldata"
    )
    cursor = conn.cursor()

    # Hapus data lama hanya untuk OTA yang digunakan
    ota_name = data[0]["ota"]
    cursor.execute("DELETE FROM harga_hotel WHERE ota = %s", (ota_name,))
    print(f"🗑️  Data lama untuk OTA '{ota_name}' dihapus.")

    for row in data:
        cursor.execute("""
            INSERT INTO harga_hotel (property_name, type, kompetitor_dari, tanggal, harga, ota)
            VALUES (%s, %s, %s, %s, %s, %s)
        """, (row["name"], row["type"], row.get("of"), row["date"], row["price"], row["ota"]))
    conn.commit()
    conn.close()

def scrape_traveloka():
    with open("input.json") as f:
        properties = json.load(f)

    driver = create_driver()
    wait = WebDriverWait(driver, 15)
    hasil = []

    for hotel in properties:
        if hotel["ota"].lower() != "traveloka":
            continue

        hotel_id = hotel["hotel_id"]
        hotel_label = hotel["hotel_slug"].strip().title()

        for i in range(2):
            checkin = datetime.today() + timedelta(days=i)
            checkout = checkin + timedelta(days=1)
            checkin_str = checkin.strftime('%d-%m-%Y')
            checkout_str = checkout.strftime('%d-%m-%Y')

            url = f"https://www.traveloka.com/id-id/hotel/detail?spec={checkin_str}.{checkout_str}.1.1.HOTEL.{hotel_id}"
            driver.get(url)

            try:
                price_elem = wait.until(EC.presence_of_element_located(
                    (By.CSS_SELECTOR, "div[data-testid='overview_cheapest_price']")))
                price = price_elem.text.strip().replace("Rp", "").replace(".", "").replace(",", "").strip()
            except Exception as e:
                price = ""
                print(f"[!] {hotel_label} - {checkin_str}: Gagal ambil harga ({e})")

            hasil.append({
                "name": hotel_label,
                "type": hotel["type"],
                "of": hotel.get("of"),
                "date": checkin.strftime('%Y-%m-%d'),
                "price": price,
                "ota": hotel["ota"]
            })

            print(f"{hotel_label} - {checkin_str}: Rp {price if price else 'GAGAL'}")
            time.sleep(2)

    driver.quit()
    save_to_mysql(hasil)
    print("✅ Data Traveloka berhasil disimpan ke MySQL.")

def scrape_agoda():
    with open("input.json") as f:
        hotel_data = json.load(f)

    driver = create_driver()
    wait = WebDriverWait(driver, 10)
    hasil = []

    for hotel in hotel_data:
        if hotel["ota"].lower() != "agoda":
            continue

        for i in range(2):
            tgl = datetime.today() + timedelta(days=i)
            tgl_str = tgl.strftime("%Y-%m-%d")

            url = f"https://www.agoda.com/{hotel['url']}.html?checkin={tgl_str}&los=1&adults=1&children=0&rooms=1&currencyCode=IDR"
            driver.get(url)

            try:
                price_elem = wait.until(EC.presence_of_element_located(
                    (By.CSS_SELECTOR, "span[data-selenium='PriceDisplay']")))
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

            print(f"{hotel['name']} - {tgl_str}: {price}")

    driver.quit()
    save_to_mysql(hasil)
    print("✅ Data Agoda berhasil disimpan ke MySQL.")

if __name__ == "__main__":
    scrape_agoda()
    scrape_traveloka()
