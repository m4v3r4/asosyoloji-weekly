# Asosyoloji Haftalık

Asosyoloji için bağımsız WordPress etkinlik ve takvim eklentisi.

## Amaç

Etkinlik verisini temadan bağımsız tutar. Tema değişse bile etkinlikler WordPress veritabanında kalır.

## Özellikler

- `Etkinlik` özel içerik türü
- Etkinlik türü taksonomisi
- Başlangıç / bitiş tarihi ve saati
- Mekan
- Şehir
- Organizatör
- Etkinlik bağlantısı
- Fiyat / ücretsiz bilgisi
- Öne çıkan görsel / afiş
- Haftalık etkinlik listesi
- Yaklaşan etkinlikler listesi
- Aylık takvim
- Klasik WordPress widget
- Gutenberg blokları
- Shortcode desteği
- Schema.org `Event` JSON-LD
- Google Calendar bağlantısı
- ICS takvim dosyası
- Geçmiş etkinlikleri aktif listeden otomatik çıkarma

## Gutenberg blokları

- **Asosyoloji: Haftalık Etkinlikler**
- **Asosyoloji: Etkinlik Takvimi**

## Widget

**Görünüm → Bileşenler → Asosyoloji: Haftalık Etkinlikler**

## Shortcode

Bu haftanın etkinlikleri:

```text
[asosyoloji_haftalik]
```

Yaklaşan etkinlikler:

```text
[asosyoloji_haftalik mode="upcoming" count="12"]
```

Şehir filtresi:

```text
[asosyoloji_haftalik city="Ankara"]
```

Aylık takvim:

```text
[asosyoloji_takvim]
```

Belirli ay:

```text
[asosyoloji_takvim year="2026" month="10"]
```

## Tema entegrasyonu

Eklenti CSS değişkenleri varsa Asosyoloji temasının renklerini otomatik kullanır:

- `--aso-primary`
- `--aso-text`
- `--aso-muted`
- `--aso-border`
- `--aso-surface`

Başka bir tema kullanıldığında kendi varsayılan renkleriyle çalışmaya devam eder.

## Lisans

GPL-3.0-or-later.


## Güncellemeler

Eklenti WordPress.org yerine kendi public GitHub deposundaki Release sürümlerini kullanır.

Yeni sürüm yayınlandığında WordPress'in normal eklenti güncelleme sistemi yeni sürümü algılar ve **Eklentiler** ekranında güncelleme bildirimi gösterir.

Release paketi adı:

`asosyoloji-weekly.zip`

Sürüm tag'i ile eklenti header/PHP sürümü aynı olmalıdır. Örneğin:

`v0.1.0`

Tag push edildiğinde repodaki tek release workflow'u kurulabilir ZIP paketini oluşturup GitHub Release'e ekler.


## 0.1.1

- Gutenberg editörüne **Etkinlik Bilgileri** sağ paneli eklendi.
- Başlangıç/bitiş tarihi ve saati, mekan, şehir, organizatör, etkinlik bağlantısı, fiyat ve ücretsiz bilgisi doğrudan editörden yönetilebilir.
- Etkinlik meta alanları WordPress REST API ile güvenli şekilde kaydedilir.
- Klasik meta box fallback olarak korunur.


## 0.1.2

- Etkinlik sorgusu haftayla veya ayla çakışan çok günlük etkinlikleri de gösterir.
- Haftalık görünüm bugünden başlayan 7 günlük aralığı kullanır.
- Eski sitedeki kayıtlı \`event\` içerik tipi için geriye dönük okuma desteği eklendi.
- Widget artık **Haftalık**, **Aylık** veya **Haftalık + Aylık sekmeli** görünüm sunar.
- Aylık takvimde önceki ve sonraki ay gezinmesi eklendi.
- Etkinlik yönetim listesine tarih, saat ve yer sütunları eklendi.
- Tarih bilgisi olmayan etkinlikler yönetim listesinde açıkça işaretlenir.


## 0.1.3

- Veri modeli orijinal Asosyoloji etkinlik yapısına yaklaştırıldı.
- Ana etkinlik içerik tipi artık \`event\`; tekil bağlantılar \`/event/...\` biçimindedir.
- Etkinlik türleri \`event-categories\`, etiketler \`event-tags\` taksonomileriyle tutulur.
- Tarih/saat alanları Events Manager uyumlu \`_event_start_date\`, \`_event_start_time\`, \`_event_end_date\`, \`_event_end_time\` meta anahtarlarını kullanır.
- Mekanlar ayrı \`location\` içerik tipinde tutulur ve etkinlikler \`_location_id\` ile bağlanır.
- Mekan adresi, şehir, bölge, posta kodu ve ülke alanları eklendi.
- Eski \`asosyoloji_event\` ve \`_aso_event_*\` verileri için otomatik geçiş eklendi.
- Eski Events Manager \`wp_em_locations\` verisini okuyabilen uyumluluk katmanı eklendi.
- Haftalık / aylık widget görünümü Asosyoloji temasının tipografi, renk ve koyu mod değişkenleriyle uyumlu hale getirildi.
- Aylık takvimde güncel gün vurgusu ve daha editoryal takvim görünümü eklendi.


## 0.1.4

- WordPress export dosyasındaki gerçek Asosyoloji veri modeli desteklenir.
- Etkinlik içerik tipi `em_event` olarak değiştirildi.
- Etkinlik türleri `em_event_type`, mekanlar `em_venue` taksonomilerinden okunur.
- Tarih ve saat verileri `em_start_date_time`, `em_end_date_time`, `em_start_time`, `em_end_time` alanlarından okunur.
- WordPress Importer ile daha önce içe alınmış `em_event` kayıtları yeniden import gerekmeden görünür.
- Gutenberg etkinlik paneli `em_*` meta alanlarını doğrudan düzenler.
- Önceki eklenti sürümlerinde oluşturulan `event` / `asosyoloji_event` kayıtları `em_event` modeline taşınır.
