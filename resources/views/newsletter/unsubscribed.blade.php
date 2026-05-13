<x-layout>
    <div class="max-w-prose mx-auto px-6 py-20 text-center">
        <h1 class="font-heading font-bold text-3xl mb-4">Je bent uitgeschreven</h1>
        <p class="text-secondary mb-8">
            Geen maandelijkse update meer van Hartverwarmers in je inbox. Je krijgt nog wel transactionele e-mails (zoals reacties op je fiches) tenzij je die ook uitschakelt in je profielinstellingen.
        </p>
        <p class="text-sm text-secondary">
            Per ongeluk gedaan? Stuur even een berichtje via <a href="mailto:{{ config('mail.from.address') }}" class="text-primary underline">{{ config('mail.from.address') }}</a> en we zetten je terug op de lijst.
        </p>
    </div>
</x-layout>
