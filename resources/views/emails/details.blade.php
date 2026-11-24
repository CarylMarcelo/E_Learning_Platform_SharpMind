<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;table-layout:fixed;margin:20px 0;border:1px solid #e4d6f1;border-radius:12px;background-color:#faf7fe;">
    @foreach($rows as $label => $value)
    <tr>
        <td width="44%" style="padding:12px 14px;vertical-align:top;color:#5b2b82;font-size:14px;line-height:1.6;overflow-wrap:anywhere;word-break:break-word;{{ !$loop->last ? 'border-bottom:1px solid #e9def3;' : '' }}">{{ $label }}</td>
        <td align="right" style="padding:12px 14px;vertical-align:top;color:#21152d;font-size:15px;font-weight:bold;line-height:1.6;overflow-wrap:anywhere;word-break:break-word;{{ !$loop->last ? 'border-bottom:1px solid #e9def3;' : '' }}">{{ $value }}</td>
    </tr>
    @endforeach
</table>
