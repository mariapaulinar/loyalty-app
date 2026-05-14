<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted' => ':attributeを承認してください。',
    'accepted_if' => ':otherが:valueの場合、:attributeを承認してください。',
    'active_url' => ':attributeは有効なURLではありません。',
    'after' => ':attributeには:dateより後の日付を指定してください。',
    'after_or_equal' => ':attributeには:date以降の日付を指定してください。',
    'alpha' => ':attributeには英字のみ使用できます。',
    'alpha_dash' => ':attributeには英字・数字・ハイフンのみ使用できます。',
    'alpha_num' => ':attributeには英字と数字のみ使用できます。',
    'array' => ':attributeは配列である必要があります。',
    'ascii' => ':attributeには1バイトの英数字と記号のみ使用できます。',
    'before' => ':attributeには:dateより前の日付を指定してください。',
    'before_or_equal' => ':attributeには:date以前の日付を指定してください。',
    'between' => [
        'array' => ':attributeは:min個から:max個の間で指定してください。',
        'file' => ':attributeは:min KBから:max KBの間で指定してください。',
        'numeric' => ':attributeは:minから:maxの間で指定してください。',
        'string' => ':attributeは:min文字から:max文字の間で指定してください。',
    ],
    'boolean' => ':attributeはtrueまたはfalseで指定してください。',
    'confirmed' => ':attributeの確認入力が一致しません。',
    'current_password' => 'パスワードが正しくありません。',
    'date' => ':attributeは有効な日付ではありません。',
    'date_equals' => ':attributeは:dateと同じ日付である必要があります。',
    'date_format' => ':attributeの形式が:formatと一致しません。',
    'decimal' => ':attributeは小数点以下:decimal桁で指定してください。',
    'declined' => ':attributeを拒否してください。',
    'declined_if' => ':otherが:valueの場合、:attributeを拒否してください。',
    'different' => ':attributeと:otherは異なる必要があります。',
    'digits' => ':attributeは:digits桁で指定してください。',
    'digits_between' => ':attributeは:min桁から:max桁の間で指定してください。',
    'dimensions' => ':attributeの画像サイズが無効です。',
    'distinct' => ':attributeに重複した値があります。',
    'doesnt_end_with' => ':attributeの末尾に次のいずれかは使用できません: :values。',
    'doesnt_start_with' => ':attributeの先頭に次のいずれかは使用できません: :values。',
    'email' => ':attributeは有効なメールアドレスである必要があります。',
    'ends_with' => ':attributeの末尾は次のいずれかで終わる必要があります: :values。',
    'enum' => '選択された:attributeは無効です。',
    'exists' => '選択された:attributeは無効です。',
    'file' => ':attributeはファイルである必要があります。',
    'filled' => ':attributeは必須です。',
    'gt' => [
        'array' => ':attributeは:value個より多く指定してください。',
        'file' => ':attributeは:value KBより大きくしてください。',
        'numeric' => ':attributeは:valueより大きくしてください。',
        'string' => ':attributeは:value文字より多くしてください。',
    ],
    'gte' => [
        'array' => ':attributeは:value個以上で指定してください。',
        'file' => ':attributeは:value KB以上で指定してください。',
        'numeric' => ':attributeは:value以上で指定してください。',
        'string' => ':attributeは:value文字以上で指定してください。',
    ],
    'image' => ':attributeは画像ファイルである必要があります。',
    'in' => '選択された:attributeは無効です。',
    'in_array' => ':attributeは:otherに存在しません。',
    'integer' => ':attributeは整数である必要があります。',
    'ip' => ':attributeは有効なIPアドレスである必要があります。',
    'ipv4' => ':attributeは有効なIPv4アドレスである必要があります。',
    'ipv6' => ':attributeは有効なIPv6アドレスである必要があります。',
    'json' => ':attributeは有効なJSON文字列である必要があります。',
    'lowercase' => ':attributeは小文字で指定してください。',
    'lt' => [
        'array' => ':attributeは:value個未満で指定してください。',
        'file' => ':attributeは:value KB未満で指定してください。',
        'numeric' => ':attributeは:value未満で指定してください。',
        'string' => ':attributeは:value文字未満で指定してください。',
    ],
    'lte' => [
        'array' => ':attributeは:value個以下で指定してください。',
        'file' => ':attributeは:value KB以下で指定してください。',
        'numeric' => ':attributeは:value以下で指定してください。',
        'string' => ':attributeは:value文字以下で指定してください。',
    ],
    'mac_address' => ':attributeは有効なMACアドレスである必要があります。',
    'max' => [
        'array' => ':attributeは:max個以下で指定してください。',
        'file' => ':attributeは:max KB以下で指定してください。',
        'numeric' => ':attributeは:max以下で指定してください。',
        'string' => ':attributeは:max文字以下で指定してください。',
    ],
    'max_digits' => ':attributeは:max桁以下で指定してください。',
    'mimes' => ':attributeは次の形式のファイルである必要があります: :values。',
    'mimetypes' => ':attributeは次のMIMEタイプのファイルである必要があります: :values。',
    'min' => [
        'array' => ':attributeは:min個以上で指定してください。',
        'file' => ':attributeは:min KB以上で指定してください。',
        'numeric' => ':attributeは:min以上で指定してください。',
        'string' => ':attributeは:min文字以上で指定してください。',
    ],
    'min_digits' => ':attributeは:min桁以上で指定してください。',
    'missing' => ':attributeは指定しないでください。',
    'missing_if' => ':otherが:valueの場合、:attributeは指定しないでください。',
    'missing_unless' => ':otherが:valueでない限り、:attributeは指定しないでください。',
    'missing_with' => ':valuesがある場合、:attributeは指定しないでください。',
    'missing_with_all' => ':valuesがすべてある場合、:attributeは指定しないでください。',
    'multiple_of' => ':attributeは:valueの倍数である必要があります。',
    'not_in' => '選択された:attributeは無効です。',
    'not_regex' => ':attributeの形式が無効です。',
    'numeric' => ':attributeは数値である必要があります。',
    'password' => [
        'letters' => ':attributeには少なくとも1文字の英字を含めてください。',
        'mixed' => ':attributeには少なくとも1文字の大文字と小文字を含めてください。',
        'numbers' => ':attributeには少なくとも1つの数字を含めてください。',
        'symbols' => ':attributeには少なくとも1つの記号を含めてください。',
        'uncompromised' => '指定された:attributeは漏えいデータに含まれています。別の:attributeを選択してください。',
    ],
    'present' => ':attributeは存在している必要があります。',
    'prohibited' => ':attributeは指定できません。',
    'prohibited_if' => ':otherが:valueの場合、:attributeは指定できません。',
    'prohibited_unless' => ':otherが:valuesに含まれていない限り、:attributeは指定できません。',
    'prohibits' => ':attributeがある場合、:otherは指定できません。',
    'regex' => ':attributeの形式が無効です。',
    'required' => ':attributeは必須です。',
    'required_array_keys' => ':attributeには次のキーが必要です: :values。',
    'required_if' => ':otherが:valueの場合、:attributeは必須です。',
    'required_if_accepted' => ':otherが承認されている場合、:attributeは必須です。',
    'required_unless' => ':otherが:valuesに含まれていない場合、:attributeは必須です。',
    'required_with' => ':valuesがある場合、:attributeは必須です。',
    'required_with_all' => ':valuesがすべてある場合、:attributeは必須です。',
    'required_without' => ':valuesがない場合、:attributeは必須です。',
    'required_without_all' => ':valuesがすべてない場合、:attributeは必須です。',
    'same' => ':attributeと:otherが一致している必要があります。',
    'size' => [
        'array' => ':attributeは:size個である必要があります。',
        'file' => ':attributeは:size KBである必要があります。',
        'numeric' => ':attributeは:sizeである必要があります。',
        'string' => ':attributeは:size文字である必要があります。',
    ],
    'starts_with' => ':attributeは次のいずれかで始まる必要があります: :values。',
    'string' => ':attributeは文字列である必要があります。',
    'timezone' => ':attributeは有効なタイムゾーンである必要があります。',
    'ulid' => ':attributeは有効なULIDである必要があります。',
    'unique' => ':attributeはすでに使用されています。',
    'uploaded' => ':attributeのアップロードに失敗しました。',
    'uppercase' => ':attributeは大文字で指定してください。',
    'url' => ':attributeの形式が無効です。',
    'uuid' => ':attributeは有効なUUIDである必要があります。',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'カスタムメッセージ',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [
        'address' => '住所',
        'age' => '年齢',
        'amount' => '金額',
        'area' => 'エリア',
        'available' => '利用可能',
        'birthday' => '誕生日',
        'body' => '本文',
        'city' => '市区町村',
        'content' => '内容',
        'country' => '国',
        'created_at' => '作成日時',
        'creator' => '作成者',
        'current_password' => '現在のパスワード',
        'date' => '日付',
        'date_of_birth' => '生年月日',
        'day' => '日',
        'deleted_at' => '削除日時',
        'description' => '説明',
        'district' => '地区',
        'duration' => '期間',
        'email' => 'メールアドレス',
        'excerpt' => '抜粋',
        'filter' => 'フィルター',
        'first_name' => '名',
        'gender' => '性別',
        'group' => 'グループ',
        'hour' => '時間',
        'image' => '画像',
        'last_name' => '姓',
        'lesson' => 'レッスン',
        'line_address_1' => '住所1',
        'line_address_2' => '住所2',
        'message' => 'メッセージ',
        'middle_name' => 'ミドルネーム',
        'minute' => '分',
        'mobile' => '携帯電話',
        'month' => '月',
        'name' => '名前',
        'national_code' => '国コード',
        'number' => '番号',
        'password' => 'パスワード',
        'password_confirmation' => 'パスワード確認',
        'phone' => '電話番号',
        'photo' => '写真',
        'postal_code' => '郵便番号',
        'price' => '価格',
        'province' => '都道府県',
        'recaptcha_response_field' => 'reCAPTCHA応答',
        'remember' => 'ログイン状態を保持',
        'restored_at' => '復元日時',
        'result_text_under_image' => '画像下のテキスト',
        'role' => '権限',
        'second' => '秒',
        'sex' => '性別',
        'short_text' => '短文',
        'size' => 'サイズ',
        'state' => '州',
        'street' => '番地',
        'student' => '受講者',
        'subject' => '件名',
        'teacher' => '講師',
        'terms' => '利用規約',
        'test_description' => 'テスト説明',
        'test_locale' => 'テストロケール',
        'test_name' => 'テスト名',
        'text' => 'テキスト',
        'time' => '時刻',
        'title' => 'タイトル',
        'updated_at' => '更新日時',
        'username' => 'ユーザー名',
        'year' => '年',
    ],

];
