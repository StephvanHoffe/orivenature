{!! '<'.'?xml version="1.0" encoding="UTF-8"?>' !!}
<rss xmlns:g="http://base.google.com/ns/1.0" version="2.0">
  <channel>
    <title>{{ settings('store.name') }}</title>
    <link>{{ url('/') }}</link>
    <description>{{ settings('seo.description') }}</description>
    @foreach($products as $product)
      @foreach($product->variants as $variant)
        <item>
          <g:id>{{ $variant->id }}</g:id>
          <g:item_group_id>{{ $product->id }}</g:item_group_id>
          <g:title>{{ $variant->displayTitle() }}</g:title>
          <g:description>{{ \Illuminate\Support\Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags((string) ($product->description ?: $product->short_description ?: $product->title)))), 4900) }}</g:description>
          <g:link>{{ url($product->url($variant)) }}</g:link>
          @if($img = $variant->imageUrl(1200))<g:image_link>{{ $img }}</g:image_link>@endif
          @foreach($product->images->skip(1)->take(9) as $image)<g:additional_image_link>{{ $image->url(1200) }}</g:additional_image_link>@endforeach
          <g:brand>{{ settings('store.name') }}</g:brand>
          <g:condition>new</g:condition>
          <g:availability>{{ $variant->isAvailable() ? 'in stock' : 'out of stock' }}</g:availability>
          <g:price>{{ number_format(($variant->compare_at_price > $variant->price ? $variant->compare_at_price : $variant->price) / 100, 2, '.', '') }} EUR</g:price>
          @if($variant->compare_at_price > $variant->price)<g:sale_price>{{ number_format($variant->price / 100, 2, '.', '') }} EUR</g:sale_price>@endif
          @if($variant->sku)<g:mpn>{{ $variant->sku }}</g:mpn>@endif
          @if($product->product_type)<g:product_type>{{ $product->product_type }}</g:product_type>@endif
          @if(! $product->hasOnlyDefaultVariant())<g:size>{{ $variant->title }}</g:size>@endif
          <g:shipping><g:country>NL</g:country><g:price>0.00 EUR</g:price></g:shipping>
        </item>
      @endforeach
    @endforeach
  </channel>
</rss>
