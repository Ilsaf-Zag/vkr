import { apiClient } from '../api/client';

export function listItems(data: any): any[] {
  if (Array.isArray(data?.items?.data)) return data.items.data;
  if (Array.isArray(data?.items)) return data.items;
  if (Array.isArray(data?.data)) return data.data;
  if (Array.isArray(data?.payload)) return data.payload;
  if (Array.isArray(data)) return data;
  return [];
}

export function validationErrors(error: any): Record<string, string> {
  const errors = error?.response?.data?.errors;
  if (!errors) {
    return { submit: error?.response?.data?.message ?? 'Не удалось выполнить действие' };
  }

  return Object.fromEntries(
    Object.entries(errors).map(([key, value]) => [
      key,
      Array.isArray(value) ? String(value[0]) : String(value),
    ]),
  );
}

export function dateOnly(value?: string | null): string {
  if (!value) return '';
  return String(value).slice(0, 10);
}

export function dateTime(value?: string | null): string {
  if (!value) return '—';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return String(value);
  return date.toLocaleString('ru-RU');
}

export function enumLabel(value?: string | null): string {
  const labels: Record<string, string> = {
    admin: 'Администратор',
    dispatcher: 'Диспетчер',
    medic: 'Медик',
    mechanic: 'Механик',
    driver: 'Водитель',
    active: 'Активен',
    inactive: 'Неактивен',
    blocked: 'Заблокирован',
    available: 'Доступен',
    on_line: 'На линии',
    maintenance: 'Обслуживание',
    petrol: 'Бензин',
    gas: 'Газ',
    diesel: 'Дизель',
    planned: 'Запланирован',
    completed: 'Завершен',
    cancelled: 'Отменен',
    day: 'Дневная',
    night: 'Ночная',
    pre_trip: 'Предрейсовый',
    post_trip: 'Послерейсовый',
    pending: 'Ожидает',
    approved: 'Допущен',
    rejected: 'Отклонен',
    opened: 'Открыт',
    pre_med_requested: 'Ожидает предрейсовый медосмотр',
    pre_med_rejected: 'Предрейсовый медосмотр отклонен',
    pre_med_approved: 'Предрейсовый медосмотр пройден',
    pre_tech_requested: 'Ожидает предрейсовый техосмотр',
    pre_tech_rejected: 'Предрейсовый техосмотр отклонен',
    pre_tech_approved: 'Предрейсовый техосмотр пройден',
    initial_print_pending: 'Ожидает первую печать',
    initial_printed: 'Первая печать выполнена',
    shift_started: 'Смена начата',
    shift_in_progress: 'Смена в процессе',
    return_started: 'Рейс завершен',
    post_med_requested: 'Ожидает послерейсовый медосмотр',
    post_med_rejected: 'Послерейсовый медосмотр отклонен',
    post_med_approved: 'Послерейсовый медосмотр пройден',
    post_tech_requested: 'Ожидает послерейсовый техосмотр',
    post_tech_rejected: 'Послерейсовый техосмотр отклонен',
    post_tech_approved: 'Послерейсовый техосмотр пройден',
    final_print_pending: 'Ожидает итоговую печать',
    final_printed: 'Итоговая печать выполнена',
    closed: 'Закрыт',
    odometer_photo: 'Фото одометра',
    pending_recognition: 'Ожидает распознавания',
    recognized: 'Распознано',
    failed: 'Ошибка распознавания',
    confirmed: 'Подтверждено',
    corrected: 'Исправлено вручную',
    start: 'Начало',
    finish: 'Конец',
  };

  return value ? labels[value] ?? value : '—';
}

export function cleanPayload<T extends Record<string, any>>(payload: T): T {
  return Object.fromEntries(
    Object.entries(payload).map(([key, value]) => [key, value === '' ? null : value]),
  ) as T;
}

export async function openBlob(path: string, filename: string) {
  const response = await apiClient.get(path, { responseType: 'blob' } as any);
  const url = window.URL.createObjectURL(response.data);
  const link = document.createElement('a');
  link.href = url;
  link.target = '_blank';
  link.rel = 'noopener';
  link.download = filename;
  document.body.appendChild(link);
  link.click();
  link.remove();
  window.setTimeout(() => window.URL.revokeObjectURL(url), 60_000);
}
