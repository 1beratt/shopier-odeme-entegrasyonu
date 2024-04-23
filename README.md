# Shopier Ödeme Entegrasyonu

Bu repo, Laravel ve Shopier arasında ödeme işlemleri için basit bir entegrasyon örneği içerir. Bu proje, kullanıcının sipariş oluşturmasını, Shopier ile ödeme yapmasını ve ödeme sonuçlarının Shopier geri dönüşü üzerinden alınmasını sağlar. 

(Kodları, yeni başlayanlar için daha anlaşılır olsun diye minimal düzeyde tuttum.)

## İçindekiler
- [Gereksinimler](#gereksinimler)
- [Kullanım](#kullanım)
- [Fonksiyonlar](#fonksiyonlar)
- [API Anahtarı ve Gizli Kod](#api-anahtarı-ve-gizli-kod)
- [Lisans](#lisans)

## Gereksinimler
- PHP 8.0 veya üzeri
- Laravel 8.x veya üzeri
- Shopier API KEY ve API SECRET

## Kullanım

- Kullanıcılar bir paket seçtiklerinde ve sipariş verdiklerinde, ödeme sayfasına yönlendirilirler.
- Ödeme işlemi tamamlandığında, Shopier'den bir geri bildirim alır ve sipariş durumu güncellenir.

## Fonksiyonlar

- **order**: Kullanıcıların sipariş vermesine olanak tanır.
- **generate_shopier_form**: Shopier ödeme formunu oluşturur.
- **shopierCallback**: Shopier'den gelen geri bildirimleri işler ve sipariş durumunu günceller.
- **generateOrderNumber**: Benzersiz bir sipariş numarası oluşturur.

## API Anahtarı ve API Secret

- API anahtarınızı ve API Secret kodunuzu `order` ve `shopierCallback` fonksiyonlarında tanımlamanız gerekmektedir. Bu bilgileri `.env` dosyanızda saklayabilirsiniz.

# License
This project is licensed under the MIT License. See the LICENSE file for details.
