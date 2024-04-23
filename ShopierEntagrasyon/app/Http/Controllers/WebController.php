<?php

namespace App\Http\Controllers;
use App\Models\User;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;


class WebController extends Controller
{
  public function order(Request $request)
  {
      $user = auth()->user();
      $ip = request()->ip();
      $user_id = $user?->id;

      $order_number = $this->generateOrderNumber();

      $order = new Order;
      $order->package_name = $request->package_name;
      $order->price = $request->price;
      $order->order_no = $order_number;
      $order->user_id = $user_id;
      $order->ip_address = $ip;
      $order->status = "Ödeme Alınmadı";
      $order->save();

      // Shopier ödeme formunu oluşturmak için verileri hazırlayın
      $form_data = [
          "apikey" => "BURAYA_KENDİ_KEYİNİZİ_GİRİN", //Shopier > Entegrasyonlar > Modül Yönetimi > API KULLANICI
          "apisecret" => "BURAYA_KENDİ_SECRET_KODUNUZU_GİRİN", //Shopier > Entegrasyonlar > Modül Yönetimi > API ŞİFRE
          "item_name" => $request->package_name,
          "order_id" => $order_number,
          "buyer_name" => $user->name,
          "buyer_surname" => $user->surname,
          "buyer_email" => $user->email,
          "buyer_phone" => $user->phone,
          "city" => $user->city,
          "billing_address" => $user->address,
          "ucret" => $request->price
      ];

      // Shopier ödeme formunu oluşturun
      $shopier_form = $this->generate_shopier_form($form_data);

      // Kullanıcıyı Shopier ödeme sayfasına yönlendirin
      return response($shopier_form);
  }

  public function generate_shopier_form($data)
  {
      //BU FONKSİYONDA DEĞİŞİKLİK YAPMANIZA GEREK YOK!

      $api_key = $data['apikey'];
      $secret = $data['apisecret'];
      $buyer_account_age = (int)((time() - strtotime(date("Y.m.d"))) / 86400);
      $currency = 0;
      $product_info =  $data['item_name'];
      $product_type = 1;
      $modul_version = '1.0.4';
      $random_number = rand(1000000, 9999999);
      $args = [
          'API_key' => $api_key,
          'website_index' => 1,
          'platform_order_id' => $data['order_id'],
          'product_name' => $product_info,
          'product_type' => $product_type,
          'buyer_name' => $data['buyer_name'],
          'buyer_surname' => $data['buyer_surname'],
          'buyer_email' => $data['buyer_email'],
          'buyer_account_age' => $buyer_account_age,
          'buyer_id_nr' => 0,
          'buyer_phone' => $data['buyer_phone'],
          'billing_address' => $data['billing_address'],
          'billing_city' => $data['city'],
          'billing_country' => "TR",
          'billing_postcode' => "",
          'shipping_address' => $data['billing_address'],
          'shipping_city' => $data['city'],
          'shipping_country' => "TR",
          'shipping_postcode' => "",
          'total_order_value' => $data['ucret'],
          'currency' => $currency,
          'platform' => 0,
          'is_in_frame' => 1,
          'current_language' => 0,
          'modul_version' => $modul_version,
          'random_nr' => $random_number
      ];

      $signature_data = $args['random_nr'] . $args['platform_order_id'] . $args['total_order_value'] . $args['currency'];
      $signature = hash_hmac("SHA256", $signature_data, $secret, true);
      $signature = base64_encode($signature);
      $args['signature'] = $signature;

      $args_array = [];
      foreach ($args as $key => $value) {
          $args_array[] = "<input type='hidden' name='$key' value='$value' />";
      }

      return '<html><!doctype html><head><meta charset="UTF-8">
          <meta content="True" name="HandheldFriendly">
          <meta http-equiv="X-UA-Compatible" content="IE=edge">
          <meta name="robots" content="noindex, nofollow, noarchive" />
          <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=0" />
          <title lang="tr">Güvenli Ödeme Sayfası</title><body><head>
          <form action="https://www.shopier.com/ShowProduct/api_pay4.php" method="post" id="shopier_payment_form">' .
          implode('', $args_array) .
          '<script>document.getElementById("shopier_payment_form").submit();</script></form></body></html>';
  }

  public function shopierCallback(Request $request)
  {
      // BURADAKİ VERİLER SHOPİER ÜZERİNDEN GELECEĞİ İÇİN İLK OLARAK SHOPİER HESABINIZDA GERİ DÖNÜŞ ULSİ OLUŞTURLMALISINIZ.
      // Shopier'den gelen veriler
      $status = $request->input("status");
      $invoiceId = $request->input("platform_order_id");
      $transactionId = $request->input("payment_id");
      $installment = $request->input("installment");
      $signature = $request->input("signature");

      // Shopier Secret
      $shopierSecret = 'BURAYA_KENDİ_SECRET_KODUNUZU_GİRİN'; //Shopier > Entegrasyonlar > Modül Yönetimi > API ŞİFRE

      $random_nr = $request->input("random_nr");
      $total_order_value = $request->input("total_order_value");
      $currency = $request->input("currency");
      $data = $random_nr . $invoiceId . $total_order_value . $currency;

      // Gelen imzayı çözme ve doğrulama
      $decoded_signature = base64_decode($signature);
      $expected_signature = hash_hmac('SHA256', $data, $shopierSecret, true);

      // İmza doğrulama
      if ($decoded_signature === $expected_signature) {
          // Sipariş durumunu işleyin
          $order = Order::where('order_no', $invoiceId)->first();

          if ($order) {
              // Sipariş mevcut ise durumu güncelleyin
              $status = strtolower($status);

              if ($status == "success") {
                  // Sipariş başarılı, siparişi onaylayın ve gerekli işlemleri yapın
                  $order->status = "Ödeme Alındı";
              } else {
                  // Sipariş başarısız, iptal durumunu güncelleyin
                  $order->status = "Ödeme Alınmadı";
              }

              // Siparişi kaydedin
              $order->save();

              // Başarı veya hata durumuna göre kullanıcıyı bilgilendirin
              if ($status == "success") {
                  return response('Ödeme Alındı');
              } else {
                  return response('Ödeme Alınmadı');
              }
          } else {
              // Mevcut sipariş bulunamadığında hata ile başa çıkın
              return response('Sipariş bulunamadı', 404);
          }
      } else {
          // İmza doğrulama başarısız, hata ile başa çıkın
          return response('Geçersiz imza', 400);
      }
  }

  public function generateOrderNumber()
  {
      do {
          $orderNumber = rand(1000000, 9999999);
          $existingOrder = Order::where('order_no', $orderNumber)->first();
      } while ($existingOrder);
      return $orderNumber;
  }
}