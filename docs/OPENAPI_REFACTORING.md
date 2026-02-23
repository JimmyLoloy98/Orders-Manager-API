# Refactorización OpenAPI - Principios SOLID

## Resumen

Se ha refactorizado el sistema de anotaciones OpenAPI siguiendo los principios SOLID para simplificar y mantener el código.

## Arquitectura

### 1. OpenApiHelper (`app/Helpers/OpenApiHelper.php`)

**Responsabilidad única**: Crear anotaciones OpenAPI con valores por defecto.

**Métodos disponibles**:
- `get()` - Crear anotación GET
- `post()` - Crear anotación POST
- `put()` - Crear anotación PUT
- `patch()` - Crear anotación PATCH
- `delete()` - Crear anotación DELETE
- `queryParam()` - Crear parámetro de query
- `pathParam()` - Crear parámetro de ruta

### 2. Atributos Personalizados (`app/Attributes/OpenApi/`)

**Responsabilidad única**: Simplificar la sintaxis de anotaciones en controladores.

**Clases disponibles**:
- `Get` - Para endpoints GET
- `Post` - Para endpoints POST
- `Put` - Para endpoints PUT
- `Patch` - Para endpoints PATCH
- `Delete` - Para endpoints DELETE

### 3. Trait HasOpenApiAnnotations (`app/Traits/HasOpenApiAnnotations.php`)

**Opcional**: Proporciona métodos de conveniencia para controladores que prefieren usar métodos en lugar de atributos.

## Uso

### Ejemplo Básico

**Antes** (código repetitivo):
```php
#[OA\Get(
    path: "/clients",
    summary: "Listar todos los clientes",
    tags: ["Clients"],
    security: [["bearerAuth" => []]],
    responses: [
        new OA\Response(response: 200, description: "Lista de clientes"),
        new OA\Response(response: 401, description: "No autenticado"),
    ]
)]
public function index(Request $request)
```

**Después** (simplificado):
```php
use App\Attributes\OpenApi\Get;

#[Get("/clients", "Listar todos los clientes", "Clients")]
public function index(Request $request)
```

### Ejemplos por Tipo de Endpoint

#### GET
```php
use App\Attributes\OpenApi\Get;

#[Get("/clients/{id}", "Obtener detalles de un cliente", "Clients")]
public function show(Request $request, $id)
```

#### POST
```php
use App\Attributes\OpenApi\Post;

#[Post("/clients", "Crear un nuevo cliente", "Clients")]
public function store(Request $request)
```

#### PUT
```php
use App\Attributes\OpenApi\Put;

#[Put("/clients/{id}", "Actualizar un cliente", "Clients")]
public function update(Request $request, $id)
```

#### PATCH
```php
use App\Attributes\OpenApi\Patch;

#[Patch("/credits/{id}/status", "Actualizar estado de crédito", "Credits")]
public function updateStatus(Request $request, $id)
```

#### DELETE
```php
use App\Attributes\OpenApi\Delete;

#[Delete("/clients/{id}", "Eliminar un cliente", "Clients")]
public function destroy(Request $request, $id)
```

### Endpoints sin Autenticación

```php
#[Post("/auth/login", "Iniciar sesión", "Authentication", false)]
public function login(Request $request)
```

### Endpoints con Request Body Personalizado

```php
use OpenApi\Attributes as OA;

#[Post("/auth/login", "Iniciar sesión", "Authentication", false, 
    new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ["email", "password"],
            properties: [
                new OA\Property(property: "email", type: "string", format: "email"),
                new OA\Property(property: "password", type: "string", format: "password"),
            ]
        )
    )
)]
public function login(Request $request)
```

## Principios SOLID Aplicados

### 1. Single Responsibility Principle (SRP)
- `OpenApiHelper`: Solo crea anotaciones OpenAPI
- Atributos personalizados: Solo simplifican la sintaxis
- Controladores: Solo manejan lógica de negocio

### 2. Open/Closed Principle (OCP)
- Puedes extender el sistema agregando nuevos tipos de atributos sin modificar el código existente
- El helper puede ser extendido sin cambiar los controladores

### 3. Liskov Substitution Principle (LSP)
- Los atributos personalizados pueden usarse en lugar de los atributos de OpenAPI originales
- Son completamente compatibles con la librería OpenAPI

### 4. Interface Segregation Principle (ISP)
- Cada método del helper tiene un propósito específico
- No hay dependencias innecesarias entre componentes

### 5. Dependency Inversion Principle (DIP)
- Los controladores dependen de abstracciones (atributos) no de implementaciones concretas
- El helper puede ser reemplazado sin afectar los controladores

## Beneficios

1. **Menos código repetitivo**: Reducción de ~70% en líneas de código para anotaciones
2. **Mantenibilidad**: Cambios en respuestas por defecto se hacen en un solo lugar
3. **Consistencia**: Todas las anotaciones siguen el mismo patrón
4. **Legibilidad**: Código más limpio y fácil de entender
5. **Extensibilidad**: Fácil agregar nuevos tipos de endpoints o personalizaciones

## Migración

Para migrar un controlador existente:

1. Importar los atributos necesarios:
```php
use App\Attributes\OpenApi\Get;
use App\Attributes\OpenApi\Post;
// etc.
```

2. Reemplazar las anotaciones complejas con las simplificadas:
```php
// Antes
#[OA\Get(path: "/endpoint", ...)]

// Después
#[Get("/endpoint", "Descripción", "Tag")]
```

3. Para casos especiales (requestBody, parámetros personalizados), usar los parámetros opcionales del atributo.
