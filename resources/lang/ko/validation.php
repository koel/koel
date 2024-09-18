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

    'accepted' => ':attribute을(를) 수락해야 합니다.',
    'active_url' => ':attribute은(는) 유효한 URL이 아닙니다.',
    'after' => ':attribute은(는) :date 이후의 날짜여야 합니다.',
    'alpha' => ':attribute은(는) 문자만 포함될 수 있습니다.',
    'alpha_dash' => ':attribute은(는) 문자, 숫자, 대시만 포함될 수 있습니다.',
    'alpha_num' => ':attribute은(는) 문자와 숫자만 포함할 수 있습니다.',
    'array' => ':attribute은(는) 배열이어야 합니다.',
    'before' => ':attribute은(는) :date 이전의 날짜여야 합니다.',
    'between' => [
        'numeric' => ':attribute은(는) :min와(과) :max 사이여야 합니다.',
        'file' => ':attribute은(는) :min와(과) :max 로바이트 사이여야 합니다.',
        'string' => ':attribute은(는) :min와(과) :max 문자 사이여야 합니다.',
        'array' => ':attribute은(는) :min와(과) :max 항목 사이여야 합니다.',
    ],
    'boolean' => ':attribute 필드는 true 또는 false여야 합니다.',
    'confirmed' => ':attribute 확인이 일치하지 않습니다.',
    'date' => ':attribute이(가) 유효한 날짜가 아닙니다.',
    'date_format' => ':attribute이(가) :format 형식과 일치하지 않습니다.',
    'different' => ':attribute와(과) :other은(는) 달라야 합니다.',
    'digits' => ':attribute은(는) :digits 숫자여야 합니다.',
    'digits_between' => ':attribute은(는) :min과 :max 숫자 사이여야 합니다.',
    'email' => ':attribute은(는) 유효한 이메일 주소여야 합니다.',
    'exists' => '선택한 :attribute이(가) 잘못되었습니다.',
    'filled' => ':attribute 필드는 필수입니다.',
    'image' => ':attribute은(는) 이미지여야 합니다.',
    'in' => '선택한 :attribute이(가) 잘못되었습니다.',
    'integer' => ':attribute은(는) 정수여야 합니다.',
    'ip' => ':attribute은(는) 유효한 IP 주소여야 합니다.',
    'json' => ':attribute은(는) 유효한 JSON 문자열이어야 합니다.',
    'max' => [
        'numeric' => ':attribute은(는) :max 보다 클 수 없습니다.',
        'file' => ':attribute은(는) :max 킬로바이트보다 클 수 없습니다.',
        'string' => ':attribute은(는) :max 문자보다 클 수 없습니다.',
        'array' => ':attribute은(는) :max 개 이상의 항목을 가질 수 없습니다',
    ],
    'mimes' => ':attribute은(는) 다음 유형의 파일이어야 합니다: :values',
    'min' => [
        'numeric' => ':attribute은(는) 최소 :min 이상이어야 합니다.',
        'file' => ':attribute은(는) 최소 :min 킬로바이트 이상이어야 합니다.',
        'string' => ':attribute은(는) 최소 :min 문자 이상이어야 합니다.',
        'array' => ':attribute은(는) 최소 :min 개의 항목이 있어야 합니다.',
    ],
    'not_in' => '선택한 :attribute이(가) 잘못되었습니다.',
    'numeric' => ':attribute은(는) 숫자여야 합니다.',
    'regex' => ':attribute 형식이 잘못되었습니다.',
    'required' => ':attribute 필드는 필수입니다.',
    'required_if' => ':other이(가) :value 일 때 :attribute 필드는 필수입니다.',
    'required_unless' => ':other이(가) :values에 없는 경우 :attribute 필드가 필요합니다.',
    'required_with' => ':values이(가) 있는 경우 :attribute 필드가 필요합니다.',
    'required_with_all' => ':values이(가) 있는 경우 :attribute 필드가 필요합니다.',
    'required_without' => ':values이(가) 없는 경우 :attribute 필드가 필요합니다.',
    'required_without_all' => ':values이(가) 하나도 없는 경우 :attribute 필드가 필요합니다.',
    'same' => ':attribute와(과) :other은(는) 일치해야 합니다.',
    'size' => [
        'numeric' => ':attribute은(는) :size여야 합니다.',
        'file' => ':attribute은(는) :size 킬로바이트여야 합니다.',
        'string' => ':attribute은(는) :size 문자여야 합니다.',
        'array' => ':attribute은(는) :size 항목을 포함해야 합니다.',
    ],
    'string' => ':attribute은(는) 문자열이어야 합니다.',
    'timezone' => ':attribute은(는) 유효한 시간대여야 합니다.',
    'unique' => ':attribute은(는) 이미 사용되었습니다.',
    'url' => ':attribute 형식이 잘못되었습니다.',

    'path' => [
        'valid' => ':attribute은(는) 유효하지 않거나 읽을 수 있는 경로가 아닙니다.',
    ],

    'uploaded' => ':attribute은(는) 업로드에 실패하였습니다.',

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
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap attribute place-holders
    | with something more reader friendly such as E-Mail Address instead
    | of "email". This simply helps us make messages a little cleaner.
    |
    */

    'attributes' => [],
];
