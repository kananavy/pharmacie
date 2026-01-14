export const CURRENCY_CODE = 'MGA'
export const LOCALE = 'fr-MG'

export function formatCurrency(amount: number | null | undefined): string {
  const value = amount ?? 0
  return new Intl.NumberFormat(LOCALE, {
    style: 'currency',
    currency: CURRENCY_CODE,
    currencyDisplay: 'symbol',
    maximumFractionDigits: 0
  }).format(value)
}

export function formatDate(date: string | Date): string {
  const d = typeof date === 'string' ? new Date(date) : date
  return new Intl.DateTimeFormat(LOCALE, {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  }).format(d)
}

