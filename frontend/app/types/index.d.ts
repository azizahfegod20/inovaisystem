import type { AvatarProps } from '@nuxt/ui'

export type UserStatus = 'subscribed' | 'unsubscribed' | 'bounced'
export type SaleStatus = 'paid' | 'failed' | 'refunded'

export interface User {
  id: number
  name: string
  email: string
  avatar?: AvatarProps
  status: UserStatus
  location: string
}

export interface Mail {
  id: number
  unread?: boolean
  from: User
  subject: string
  body: string
  date: string
}

export interface Member {
  name: string
  username: string
  role: 'member' | 'owner'
  avatar: AvatarProps
}

export interface Stat {
  title: string
  icon: string
  value: number | string
  variation: number
  formatter?: (value: number) => string
}

export interface Sale {
  id: string
  date: string
  status: SaleStatus
  email: string
  amount: number
}

export interface Notification {
  id: number
  unread?: boolean
  sender: User
  body: string
  date: string
}

export type Period = 'daily' | 'weekly' | 'monthly'

export interface Range {
  start: Date
  end: Date
}

// ── NFS-e Types ──────────────────────────────────────────

export type InvoiceStatus = 'pending' | 'processing' | 'authorized' | 'rejected' | 'cancelled' | 'replaced' | 'error'

export interface AuthUser {
  id: number
  name: string
  email: string
}

export interface CompanySummary {
  id: number
  cnpj: string
  razao_social: string
  role: string
}

export interface Company {
  id: number
  cnpj: string
  razao_social: string
  nome_fantasia?: string
  inscricao_municipal?: string
  inscricao_estadual?: string
  logradouro: string
  numero: string
  complemento?: string
  bairro: string
  codigo_ibge: string
  uf: string
  cep: string
  telefone?: string
  email?: string
  regime_tributario: number
  reg_esp_trib: number
  dps_serie: string
  dps_next_number: number
  ambiente: number
}

export interface Certificate {
  id: number
  cnpj: string
  common_name: string
  valid_from: string
  valid_to: string
  is_active: boolean
  is_expired?: boolean
  is_expiring_soon?: boolean
}

export interface Customer {
  id: number
  company_id: number
  tipo_documento: string
  documento: string
  razao_social: string
  nome_fantasia?: string
  inscricao_municipal?: string
  logradouro: string
  numero: string
  complemento?: string
  bairro: string
  codigo_ibge: string
  uf: string
  cep: string
  email?: string
  telefone?: string
}

export interface ServiceItem {
  id: number
  company_id: number
  codigo_lc116: string
  codigo_nbs?: string
  descricao: string
  aliquota_iss: number
  is_favorite: boolean
}

export interface Invoice {
  id: number
  company_id: number
  customer_id: number
  service_id: number
  user_id: number
  status: InvoiceStatus
  id_dps: string
  dps_number: number
  dps_serie: string
  chave_acesso?: string
  numero_nfse?: number
  valor_servico: number
  valor_deducoes: number
  valor_desconto: number
  valor_liquido: number
  aliquota_iss: number
  valor_iss: number
  iss_retido: boolean
  valor_ir: number
  valor_csll: number
  valor_cofins: number
  valor_pis: number
  valor_inss: number
  descricao_servico: string
  data_emissao: string
  data_cancelamento?: string
  motivo_cancelamento?: string
  customer?: Customer
  service?: ServiceItem
}

export interface DashboardStats {
  total_notas: number
  total_receita: number
  total_canceladas: number
  total_iss: number
  total_retencoes: number
}

export interface DashboardChart {
  labels: string[]
  datasets: {
    receita: number[]
    notas: number[]
  }
}

export interface CnpjLookup {
  cnpj: string
  razao_social?: string
  nome_fantasia?: string
  logradouro?: string
  numero?: string
  complemento?: string
  bairro?: string
  cep?: string
  codigo_ibge?: string
  uf?: string
  email?: string
  telefone?: string
  simples_nacional?: boolean
  mei?: boolean
}

export interface PaginatedResponse<T> {
  data: T[]
  current_page: number
  last_page: number
  per_page: number
  total: number
}
