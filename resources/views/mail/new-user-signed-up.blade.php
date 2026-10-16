<x-mail::message>
# New Exclusion Zone player

- **Name:** {{ $userName }}
- **Email:** {{ $userEmail }}
- **Time:** {{ $time }}
- **Total players:** {{ $totalPlayers }}
@if ($referrer)
- **Referrer:** {{ $referrer }}
@endif
@if ($country)
- **Country:** {{ $country }}
@endif
</x-mail::message>
