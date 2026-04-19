export interface User {
    id: number
    name: string
    email: string
    locale: string
    theme: 'light' | 'dark'
    active_organization_id: number
}

export interface Organization {
    id: number
    name: string
    default_number: string
    credits: number
    parent_id: number | null
}

export interface Subscriber {
    id: number
    organization_id: number
    first_name: string
    last_name: string
    phone: string
    email: string | null
    status: 'opted_in' | 'opted_out'
    source: string
    created_at: string
    updated_at: string
}

export interface SubscriberList {
    id: number
    organization_id: number
    name: string
    type: 'manual' | 'integration' | 'keyword'
    sync_source: string | null
    last_synced_at: string | null
    subscribers_count?: number
    created_at: string
    updated_at: string
}

export interface Message {
    id: number
    organization_id: number
    user_id: number
    body: string
    type: 'text' | 'email'
    from_number: string
    status: 'draft' | 'scheduled' | 'sent' | 'failed'
    scheduled_at: string | null
    sent_at: string | null
    recipients_count: number
    credits_used: number
    created_at: string
    updated_at: string
}

export interface Keyword {
    id: number
    organization_id: number
    name: string
    number: string
    status: 'active' | 'archived'
    workflow: KeywordWorkflowStep[]
    aliases: string[]
    uses_count?: number
    opt_ins_count?: number
    created_at: string
    updated_at: string
}

export interface KeywordWorkflowStep {
    type: 'add_to_list' | 'collect_info' | 'send_message'
    config: Record<string, unknown>
}

export interface Conversation {
    id: number
    organization_id: number
    subscriber_id: number
    subscriber?: Subscriber
    number: string
    status: 'open' | 'done'
    last_message_at: string
    last_message?: string
    unread?: boolean
}

export interface ConversationMessage {
    id: number
    conversation_id: number
    body: string
    direction: 'inbound' | 'outbound'
    user_id: number | null
    sent_at: string
}

export interface Poll {
    id: number
    organization_id: number
    question: string
    options: string[]
    created_at: string
}

export interface PaginatedResponse<T> {
    data: T[]
    current_page: number
    last_page: number
    per_page: number
    total: number
}

export interface DashboardStats {
    total_subscribers: number
    opt_ins: number
    opt_ins_delta: number
    opt_outs: number
    opt_outs_delta: number
    outgoing_texts: number
    outgoing_texts_delta: number
    incoming_texts: number
    incoming_texts_delta: number
    chart_data: ChartDataPoint[]
    subscriber_sources: SubscriberSource[]
}

export interface ChartDataPoint {
    date: string
    subscribers: number
    opt_ins: number
}

export interface SubscriberSource {
    source: string
    count: number
    percentage: number
}
