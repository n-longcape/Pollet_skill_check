## 概要
Laravel6を用いた以下の機能を作成

- 月次ポイント計算バッチ(app/Console/Commands/MonthlyCalculatePoint.php)
- ポイント付与バッチ(app/Console/Commands/ProvidePoint.php)

## 導入手順

```
docker-compose build
docker-compose up

// 別タブで開く
docker exec -it {$phpContainerID} /bin/sh
php artisan migrate
php artisan db:seed // Mockデータ挿入
```

## バッチの起動

```
docker exec -it {$phpContainerID} /bin/sh
php artisan command:{ProvidePoint|MonthlyCalculatePoint}
```

## ファイル出力場所
`storage/app` 配下に出力される。詳しい出力方法はソースコード参照
