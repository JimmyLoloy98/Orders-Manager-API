# Fix: Ordenamiento de Ventas por Hora de Pago

## Problema

La lista de ventas (`GET /orders/history`) ordenaba, filtraba y mostraba la fecha basándose en `created_at` (fecha de creación del pedido, cuando el cliente se sienta a la mesa). El comportamiento esperado es que ordene, filtre y muestre por la hora en que se realizó el pago.

```
Ejemplo:
  10:00 → Se crea el pedido (created_at)
  10:15 → Se agregan items (aumento)
  10:45 → Se paga la cuenta (pay)
  
  Antes:  la venta aparecía ordenada como de las 10:00
  Ahora:  aparece ordenada como de las 10:45
```

## Causa Raíz

El backend no guardaba el momento del pago. El método `pay()` solo cambiaba `status` a `'paid'`, sin persistir un timestamp del pago.

## Cambios Realizados

### 1. Nueva migración: `add_paid_at_to_orders_table`

**Archivo:** `database/migrations/2026_07_08_214957_add_paid_at_to_orders_table.php`

- Agrega columna `paid_at` (timestamp, nullable) a la tabla `orders`, después de `status`
- **Backfill:** actualiza las órdenes existentes con `status = 'paid'` y `paid_at IS NULL`, asignándoles `paid_at = updated_at` (momento en que se pagaron originalmente)

### 2. Actualización de `pay()` en `OrderController`

**Archivo:** `app/Http/Controllers/Api/OrderController.php:358-361`

```php
// Antes:
$order->update(['status' => 'paid']);

// Ahora:
$order->update([
    'status' => 'paid',
    'paid_at' => now(),
]);
```

### 3. Actualización de `history()` en `OrderController`

**Archivo:** `app/Http/Controllers/Api/OrderController.php:167-188`

| Aspecto | Antes (`created_at`) | Ahora (`paid_at`) |
|---------|---------------------|-------------------|
| Ordenamiento | `orderBy('created_at', 'desc')` | `orderBy('paid_at', 'desc')` |
| Filtro por fecha | `whereDate('created_at', ...)` | `whereDate('paid_at', ...)` |
| Fecha mostrada | `$order->created_at->format(...)` | `$order->paid_at?->format(...) ?? $order->created_at->format(...)` |

## Archivos Modificados

| Archivo | Cambio |
|---------|--------|
| `database/migrations/2026_07_08_214957_add_paid_at_to_orders_table.php` | ✨ Nuevo |
| `app/Http/Controllers/Api/OrderController.php` | 3 cambios: `pay()`, `history()` orden, `history()` fecha |

## Frontend

**Sin cambios.** El frontend consume `fecha_hora` de forma genérica desde `SaleHistoryItem`, por lo que al venir ahora con `paid_at` se refleja automáticamente en la tabla de ventas.

## Notas

- La columna `paid_at` es nullable porque las órdenes en estado `pending` aún no tienen fecha de pago
- Si por algún motivo `paid_at` es `null` (ej: orden pendiente), se usa `created_at` como fallback
- Las órdenes pendientes no aparecen en la lista al filtrar por fecha de pago (comportamiento esperado: son ventas no pagadas)
