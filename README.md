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
