@php
    $copy = [
        'id' => ['eyebrow' => 'PERMINTAAN PENAWARAN', 'title' => 'Ceritakan kebutuhan Anda.', 'intro' => 'Tim Auliachem akan meninjau kebutuhan produk, jumlah, dan dokumen teknis yang diperlukan.', 'name' => 'Nama lengkap', 'company' => 'Perusahaan', 'email' => 'Email bisnis', 'phone' => 'Telepon / WhatsApp', 'interest' => 'Kebutuhan', 'quantity' => 'Perkiraan jumlah', 'message' => 'Detail kebutuhan', 'submit' => 'Kirim permintaan', 'success' => 'Permintaan sudah diterima. Tim kami akan menghubungi Anda.', 'choose' => 'Pilih kebutuhan', 'optional' => 'Opsional'],
        'en' => ['eyebrow' => 'REQUEST A QUOTE', 'title' => 'Tell us what you need.', 'intro' => 'The Auliachem team will review your product, quantity, and technical-document requirements.', 'name' => 'Full name', 'company' => 'Company', 'email' => 'Business email', 'phone' => 'Phone / WhatsApp', 'interest' => 'Requirement', 'quantity' => 'Estimated quantity', 'message' => 'Requirement details', 'submit' => 'Send request', 'success' => 'Your request has been received. Our team will contact you.', 'choose' => 'Choose a requirement', 'optional' => 'Optional'],
        'zh' => ['eyebrow' => '询价申请', 'title' => '请告诉我们您的需求。', 'intro' => 'Auliachem 团队将评估您的产品、数量和技术文件需求。', 'name' => '姓名', 'company' => '公司', 'email' => '商务邮箱', 'phone' => '电话 / WhatsApp', 'interest' => '需求类别', 'quantity' => '预计数量', 'message' => '需求详情', 'submit' => '提交申请', 'success' => '您的申请已收到，我们的团队将与您联系。', 'choose' => '选择需求', 'optional' => '选填'],
    ][$locale];
    $interests = [
        'chemical' => ['id' => 'Bahan baku kimia', 'en' => 'Chemical raw material', 'zh' => '化工原料'],
        'industrial-salt' => ['id' => 'Garam industri', 'en' => 'Industrial salt', 'zh' => '工业盐'],
        'laboratory-chemical' => ['id' => 'Bahan kimia laboratorium', 'en' => 'Laboratory chemical', 'zh' => '实验室化学品'],
        'laboratory-solution' => ['id' => 'Solusi laboratorium', 'en' => 'Laboratory solution', 'zh' => '实验室解决方案'],
        'other' => ['id' => 'Kebutuhan lain', 'en' => 'Other requirement', 'zh' => '其他需求'],
    ];
@endphp
<div class="container quote-shell" id="quote-form">
    <div class="quote-intro">
        <span class="eyebrow">{{ $copy['eyebrow'] }}</span>
        <h2>{{ $copy['title'] }}</h2>
        <p>{{ $copy['intro'] }}</p>
    </div>
    @if(session('quote_status'))
        <div class="quote-success" role="status">{{ $copy['success'] }}</div>
    @endif
    @if($errors->any())
        <div class="quote-errors" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif
    <form class="quote-form" method="post" action="{{ route('quotes.store') }}">
        @csrf
        <input type="hidden" name="locale" value="{{ $locale }}">
        <div class="quote-honeypot" aria-hidden="true"><label for="website">Website</label><input id="website" name="website" tabindex="-1" autocomplete="off"></div>
        <label>{{ $copy['name'] }}<input name="name" value="{{ old('name') }}" required maxlength="120" autocomplete="name"></label>
        <label>{{ $copy['company'] }} <small>{{ $copy['optional'] }}</small><input name="company" value="{{ old('company') }}" maxlength="160" autocomplete="organization"></label>
        <label>{{ $copy['email'] }}<input name="email" type="email" value="{{ old('email') }}" required maxlength="255" autocomplete="email"></label>
        <label>{{ $copy['phone'] }} <small>{{ $copy['optional'] }}</small><input name="phone" value="{{ old('phone') }}" maxlength="40" autocomplete="tel"></label>
        <label>{{ $copy['interest'] }}<select name="interest" required><option value="">{{ $copy['choose'] }}</option>@foreach($interests as $value => $labels)<option value="{{ $value }}" @selected(old('interest') === $value)>{{ $labels[$locale] }}</option>@endforeach</select></label>
        <label>{{ $copy['quantity'] }} <small>{{ $copy['optional'] }}</small><input name="quantity" value="{{ old('quantity') }}" maxlength="100"></label>
        <label class="quote-message">{{ $copy['message'] }}<textarea name="message" required minlength="10" maxlength="3000" rows="5">{{ old('message') }}</textarea></label>
        <button class="btn quote-submit" type="submit">{{ $copy['submit'] }}</button>
    </form>
</div>
