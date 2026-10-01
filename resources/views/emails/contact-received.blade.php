@extends('emails.layout', ['title' => __('Nieuw bericht')])
@section('content')
  <h1 style="margin:0 0 14px;font-family:Georgia,'Libre Baskerville',serif;font-weight:400;font-size:26px;line-height:1.2;">{{ \App\Models\ContactMessage::TYPES[$contact->type] ?? __('Bericht') }}</h1>
  <table role="presentation" cellpadding="0" cellspacing="0" style="font-size:14px;line-height:1.6;">
    @foreach(['Naam' => $contact->name, 'E-mail' => $contact->email, 'Telefoon' => $contact->phone, 'Bedrijf' => $contact->company] + collect($contact->data ?? [])->mapWithKeys(fn ($v, $k) => [ucfirst($k) => $v])->all() as $label => $value)
      @if($value)<tr><td style="padding:2px 16px 2px 0;color:#6b6c6b;">{{ $label }}</td><td>{{ $value }}</td></tr>@endif
    @endforeach
  </table>
  @if($contact->message)
    <p style="font-size:15px;line-height:1.6;margin:18px 0 0;white-space:pre-line;">{{ $contact->message }}</p>
  @endif
  <p style="font-size:13px;color:#6b6c6b;margin:18px 0 0;">{{ __('Beantwoord deze e-mail om direct te reageren.') }}</p>
@endsection
