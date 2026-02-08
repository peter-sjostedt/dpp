# DPP API - Specifikation for Windows-app

## Oversikt

DPP (Digital Product Passport) API for textil- och modeindustrin.
Multi-tenant REST API med JSON request/response.

- **Bas-URL:** `http://localhost/dpp/api.php?route=/api/...` (Laragon) eller `https://din-doman.se/api/...`
- **Format:** JSON (Content-Type: application/json)
- **CORS:** Tillater alla origins

---

## Autentisering

### Tenant API (Brand / Supplier)
Alla vanliga endpoints kraver `X-API-Key` header.

```
X-API-Key: <api_key_fran_brands_eller_suppliers_tabellen>
```

API-nyckeln avgoor om du ar autentiserad som **brand** eller **supplier**:
- **Brand:** Full CRUD pa egna resurser
- **Supplier:** Lasatkomst till relaterade brands resurser

### Admin API
Admin-endpoints (`/api/admin/*`) kraver `X-Admin-Key` header.

```
X-Admin-Key: dpp_admin_master_key_2024_secure
```

### Publika endpoints (ingen auth)
- `GET /api/tenants/brands` - Lista aktiva brands med API-nycklar
- `GET /api/tenants/suppliers` - Lista aktiva suppliers med API-nycklar

---

## Response-format

### Lyckat svar
```json
{
  "success": true,
  "data": { ... }
}
```

### Felsvar
```json
{
  "success": false,
  "error": "Felbeskrivning"
}
```

### HTTP-statuskoder
| Kod | Betydelse |
|-----|-----------|
| 200 | OK |
| 400 | Valideringsfel / Bad request |
| 401 | Saknad eller ogiltig API-nyckel |
| 403 | Ej behorig (inaktivt konto, utgangen nyckel) |
| 404 | Resursen hittades inte |
| 405 | Metoden ej tillatna |
| 500 | Serverfel |

---

## Endpoints

### 1. BRANDS

| Metod | Endpoint | Beskrivning | Auth |
|-------|----------|-------------|------|
| GET | `/api/brands` | Lista brands (filtrerade, med items) | X-API-Key |
| GET | `/api/brands/all` | Lista alla brands (for dropdowns) | X-API-Key |
| GET | `/api/brands/{id}` | Hamta ett brand | X-API-Key |
| PUT | `/api/brands/{id}` | Uppdatera brand (bara eget) | X-API-Key (brand) |

**Brand-objekt:**
```json
{
  "id": 1,
  "brand_name": "Hospitex",
  "logo_url": "https://...",
  "sub_brand": null,
  "parent_company": "Parent Corp",
  "trader": "Trader AB",
  "trader_location": "Stockholm, Sweden",
  "lei": "ABCDEFGHIJ1234567890",
  "gs1_company_prefix": "7350001",
  "api_key": "...",
  "_is_active": true,
  "created_at": "2025-01-01 00:00:00",
  "updated_at": "2025-01-01 00:00:00"
}
```

**PUT /api/brands/{id} - Body (alla falt valfria):**
```json
{
  "brand_name": "Nytt namn",
  "logo_url": "https://...",
  "sub_brand": "Sub Brand",
  "parent_company": "Parent",
  "trader": "Trader",
  "trader_location": "Location",
  "lei": "ABCDEFGHIJ1234567890",
  "gs1_company_prefix": "7350001"
}
```

---

### 2. SUPPLIERS

| Metod | Endpoint | Beskrivning | Auth |
|-------|----------|-------------|------|
| GET | `/api/suppliers` | Lista suppliers | X-API-Key |
| POST | `/api/suppliers` | Skapa supplier | X-API-Key |
| GET | `/api/suppliers/{id}` | Hamta en supplier | X-API-Key |
| PUT | `/api/suppliers/{id}` | Uppdatera supplier | X-API-Key |
| DELETE | `/api/suppliers/{id}` | Ta bort supplier | X-API-Key |

**Supplier-objekt:**
```json
{
  "id": 1,
  "supplier_name": "Textil AB",
  "supplier_location": "Boras, Sverige",
  "facility_registry": "GLN",
  "facility_identifier": "7350001000001",
  "operator_registry": null,
  "operator_identifier": null,
  "country_of_origin_confection": "SE",
  "country_of_origin_dyeing": "SE",
  "country_of_origin_weaving": "SE",
  "lei": null,
  "gs1_company_prefix": null,
  "api_key": "...",
  "_is_active": true
}
```

---

### 3. BRAND-SUPPLIER RELATIONER

| Metod | Endpoint | Beskrivning | Auth |
|-------|----------|-------------|------|
| GET | `/api/brand-suppliers` | Lista relationer | X-API-Key |
| GET | `/api/brand-suppliers/available` | Lista tillgangliga suppliers (brand) | X-API-Key (brand) |
| GET | `/api/brand-suppliers/{id}` | Hamta relation | X-API-Key |
| POST | `/api/brand-suppliers` | Skapa relation (brand) | X-API-Key (brand) |
| PUT | `/api/brand-suppliers/{id}/activate` | Aktivera relation | X-API-Key (brand) |
| PUT | `/api/brand-suppliers/{id}/deactivate` | Inaktivera relation | X-API-Key (brand) |
| DELETE | `/api/brand-suppliers/{id}` | Ta bort relation | X-API-Key (brand) |

**POST body:**
```json
{
  "supplier_id": 1
}
```

---

### 4. PRODUCTS

| Metod | Endpoint | Beskrivning | Auth |
|-------|----------|-------------|------|
| GET | `/api/products` | Lista produkter (med items-filter) | X-API-Key |
| GET | `/api/brands/{brandId}/products` | Lista produkter for ett brand | X-API-Key |
| POST | `/api/brands/{brandId}/products` | Skapa produkt | X-API-Key (brand) |
| GET | `/api/products/{id}` | Hamta produkt (inkl. care, compliance, etc.) | X-API-Key |
| PUT | `/api/products/{id}` | Uppdatera produkt | X-API-Key (brand) |
| DELETE | `/api/products/{id}` | Ta bort produkt | X-API-Key (brand) |
| GET | `/api/products/{id}/dpp` | Hamta komplett DPP-export | X-API-Key |

**Product-objekt (POST/PUT body):**
```json
{
  "product_name": "Classic T-Shirt",
  "gtin_type": "GTIN",
  "gtin": "7350001000001",
  "description": "100% organic cotton t-shirt",
  "photo_url": "https://...",
  "article_number": "TSH-001-BLK",
  "commodity_code_system": "HS",
  "commodity_code_number": "6109.10",
  "year_of_sale": 2025,
  "season_of_sale": "SP",
  "price_currency": "EUR",
  "msrp": 29.99,
  "resale_price": 15.00,
  "category": "clothing",
  "product_group": "Top",
  "type_line_concept": "Active Wear",
  "type_item": "T-Shirt",
  "age_group": "Adult",
  "gender": "Unisex",
  "market_segment": "mid-price",
  "water_properties": "Water-resistant",
  "net_weight": 0.250,
  "weight_unit": "kg",
  "data_carrier_type": "QR",
  "data_carrier_material": "Paper",
  "data_carrier_location": "Hang tag"
}
```

**Obligatoriskt falt:** `product_name`

---

### 5. PRODUCT COMPONENTS (Materialsammansattning)

| Metod | Endpoint | Beskrivning | Auth |
|-------|----------|-------------|------|
| GET | `/api/products/{productId}/components` | Lista komponenter | X-API-Key |
| POST | `/api/products/{productId}/components` | Skapa komponent | X-API-Key (brand) |
| GET | `/api/components/{id}` | Hamta komponent | X-API-Key |
| PUT | `/api/components/{id}` | Uppdatera komponent | X-API-Key (brand) |
| DELETE | `/api/components/{id}` | Ta bort komponent | X-API-Key (brand) |

**Component-objekt:**
```json
{
  "component": "Body fabric",
  "material": "Textile",
  "content_name": "Cotton",
  "content_value": 95.00,
  "content_source": "Organic",
  "material_trademarks": "Supima",
  "trim_type": null,
  "component_weight": 0.200,
  "recycled": true,
  "recycled_percentage": 30.00,
  "recycled_input_source": "Post-consumer",
  "leather_species": null,
  "leather_grade": null,
  "sewing_thread_content": "Polyester",
  "print_ink_type": "Water-based",
  "dye_class": "Reactive",
  "dye_class_standard": "Oeko-Tex",
  "finishes": "Anti-microbial",
  "pattern": "Solid"
}
```

---

### 6. PRODUCT VARIANTS (Storlek/Farg)

| Metod | Endpoint | Beskrivning | Auth |
|-------|----------|-------------|------|
| GET | `/api/products/{productId}/variants` | Lista varianter | X-API-Key |
| POST | `/api/products/{productId}/variants` | Skapa variant | X-API-Key (brand) |
| GET | `/api/variants/{id}` | Hamta variant | X-API-Key |
| PUT | `/api/variants/{id}` | Uppdatera variant | X-API-Key (brand) |
| DELETE | `/api/variants/{id}` | Ta bort variant | X-API-Key (brand) |

**Variant-objekt:**
```json
{
  "item_number": "TSH-001-BLK-M",
  "size": "M",
  "size_country_code": "SE",
  "color_brand": "Midnight Black",
  "color_general": "Black",
  "gtin": "73500010000012"
}
```

---

### 7. CARE INFORMATION (Skotselinformation)

| Metod | Endpoint | Beskrivning | Auth |
|-------|----------|-------------|------|
| GET | `/api/products/{productId}/care` | Hamta skotselinfo | X-API-Key |
| PUT | `/api/products/{productId}/care` | Skapa/uppdatera | X-API-Key (brand) |
| DELETE | `/api/products/{productId}/care` | Ta bort | X-API-Key (brand) |

**Care-objekt:**
```json
{
  "care_image_url": "https://...",
  "care_text": "Tvattas i 40 grader. Torktumla ej.",
  "safety_information": "Haller ej for oppna flammar."
}
```

---

### 8. COMPLIANCE INFORMATION

| Metod | Endpoint | Beskrivning | Auth |
|-------|----------|-------------|------|
| GET | `/api/products/{productId}/compliance` | Hamta compliance | X-API-Key |
| PUT | `/api/products/{productId}/compliance` | Skapa/uppdatera | X-API-Key (brand) |
| DELETE | `/api/products/{productId}/compliance` | Ta bort | X-API-Key (brand) |

**Compliance-objekt:**
```json
{
  "harmful_substances": "No",
  "harmful_substances_info": null,
  "certifications": "GOTS, GRS",
  "certifications_validation": "Certificate #12345",
  "chemical_compliance_standard": "REACH",
  "chemical_compliance_validation": "Test report #67890",
  "chemical_compliance_link": "https://...",
  "microfibers": "No",
  "traceability_provider": "TextileGenesis"
}
```

---

### 9. CIRCULARITY INFORMATION

| Metod | Endpoint | Beskrivning | Auth |
|-------|----------|-------------|------|
| GET | `/api/products/{productId}/circularity` | Hamta cirkularitet | X-API-Key |
| PUT | `/api/products/{productId}/circularity` | Skapa/uppdatera | X-API-Key (brand) |
| DELETE | `/api/products/{productId}/circularity` | Ta bort | X-API-Key (brand) |

**Circularity-objekt:**
```json
{
  "performance": "Designed for 5+ years of use",
  "recyclability": "Mono-material, fully recyclable",
  "take_back_instructions": "Return to any store",
  "recycling_instructions": "Remove buttons before recycling",
  "disassembly_instructions_sorters": "Cut seams, separate components",
  "disassembly_instructions_user": "Remove zipper before recycling",
  "circular_design_strategy": "Design for longevity",
  "circular_design_description": "Reinforced seams and high-quality materials",
  "repair_instructions": "Patches available at our website"
}
```

---

### 10. SUSTAINABILITY INFORMATION

| Metod | Endpoint | Beskrivning | Auth |
|-------|----------|-------------|------|
| GET | `/api/products/{productId}/sustainability` | Hamta hallbarhet | X-API-Key |
| PUT | `/api/products/{productId}/sustainability` | Skapa/uppdatera | X-API-Key (brand) |
| DELETE | `/api/products/{productId}/sustainability` | Ta bort | X-API-Key (brand) |

**Sustainability-objekt:**
```json
{
  "brand_statement": "We commit to carbon neutrality by 2030",
  "statement_link": "https://...",
  "environmental_footprint": "Carbon footprint: 5.2 kg CO2e"
}
```

---

### 11. BATCHES (Produktionsbatcher)

| Metod | Endpoint | Beskrivning | Auth |
|-------|----------|-------------|------|
| GET | `/api/batches` | Lista alla batcher | X-API-Key |
| GET | `/api/products/{productId}/batches` | Lista batcher for produkt | X-API-Key |
| POST | `/api/products/{productId}/batches` | Skapa batch | X-API-Key (brand) |
| GET | `/api/batches/{id}` | Hamta batch | X-API-Key |
| PUT | `/api/batches/{id}` | Uppdatera batch | X-API-Key (brand) |
| DELETE | `/api/batches/{id}` | Ta bort batch | X-API-Key (brand) |

**Batch-objekt:**
```json
{
  "supplier_id": 1,
  "batch_number": "BATCH-2025-001",
  "po_number": "PO-2025-100",
  "production_date": "2025-03-15",
  "quantity": 500,
  "_status": "planned"
}
```

**Status-varden:** `planned`, `in_production`, `completed`

---

### 12. BATCH MATERIALS (Koppling batch <-> fabriksmaterial)

| Metod | Endpoint | Beskrivning | Auth |
|-------|----------|-------------|------|
| GET | `/api/batches/{batchId}/materials` | Lista material i batch | X-API-Key |
| POST | `/api/batches/{batchId}/materials` | Koppla material till batch | X-API-Key |
| GET | `/api/batch-materials/{id}` | Hamta koppling | X-API-Key |
| PUT | `/api/batch-materials/{id}` | Uppdatera koppling | X-API-Key |
| DELETE | `/api/batch-materials/{id}` | Ta bort koppling | X-API-Key |

**BatchMaterial-objekt:**
```json
{
  "factory_material_id": 1,
  "component": "Body fabric"
}
```

---

### 13. ITEMS (Individuella serialiserade produkter)

| Metod | Endpoint | Beskrivning | Auth |
|-------|----------|-------------|------|
| GET | `/api/batches/{batchId}/items` | Lista items i batch | X-API-Key |
| POST | `/api/batches/{batchId}/items` | Skapa item | X-API-Key |
| POST | `/api/batches/{batchId}/items/bulk` | Skapa flera items | X-API-Key |
| GET | `/api/items/{id}` | Hamta item via ID | X-API-Key |
| GET | `/api/items/sgtin/{sgtin}` | Hamta item via SGTIN | X-API-Key |
| DELETE | `/api/items/{id}` | Ta bort item | X-API-Key |

**Item-objekt:**
```json
{
  "product_variant_id": 1,
  "unique_product_id": "UPD-001",
  "tid": "E28011700000020ABC",
  "sgtin": "urn:epc:id:sgtin:735000100.001.12345",
  "serial_number": "SN-12345"
}
```

**Bulk-skapande (POST .../items/bulk):**
```json
{
  "product_variant_id": 1,
  "quantity": 100,
  "serial_prefix": "SN-"
}
```

**Item-status:** `produced`, `shipped`, `sold`, `returned`, `recycled`

---

### 14. FACTORY MATERIALS (Fabriksmaterial)

| Metod | Endpoint | Beskrivning | Auth |
|-------|----------|-------------|------|
| GET | `/api/materials` | Lista alla material | X-API-Key |
| GET | `/api/suppliers/{supplierId}/materials` | Lista material for supplier | X-API-Key |
| POST | `/api/suppliers/{supplierId}/materials` | Skapa material | X-API-Key (supplier) |
| GET | `/api/materials/{id}` | Hamta material | X-API-Key |
| PUT | `/api/materials/{id}` | Uppdatera material | X-API-Key (supplier) |
| DELETE | `/api/materials/{id}` | Ta bort material | X-API-Key (supplier) |
| GET | `/api/materials/{materialId}/batches` | Lista batcher som anvander materialet | X-API-Key |

**FactoryMaterial-objekt:**
```json
{
  "material_name": "Organic Cotton Jersey 180gsm",
  "material_type": "Textile",
  "description": "GOTS-certified organic cotton jersey"
}
```

---

### 15. MATERIAL COMPOSITIONS (Materialsammansattning)

| Metod | Endpoint | Beskrivning | Auth |
|-------|----------|-------------|------|
| GET | `/api/materials/{materialId}/compositions` | Lista sammansattning | X-API-Key |
| POST | `/api/materials/{materialId}/compositions` | Lagg till | X-API-Key (supplier) |
| GET | `/api/compositions/{id}` | Hamta | X-API-Key |
| PUT | `/api/compositions/{id}` | Uppdatera | X-API-Key (supplier) |
| DELETE | `/api/compositions/{id}` | Ta bort | X-API-Key (supplier) |

**Composition-objekt:**
```json
{
  "content_name": "Cotton",
  "content_value": 95.00,
  "content_source": "Organic",
  "recycled": false,
  "recycled_percentage": null,
  "recycled_input_source": null
}
```

---

### 16. MATERIAL CERTIFICATIONS

| Metod | Endpoint | Beskrivning | Auth |
|-------|----------|-------------|------|
| GET | `/api/materials/{materialId}/certifications` | Lista certifieringar | X-API-Key |
| POST | `/api/materials/{materialId}/certifications` | Lagg till | X-API-Key (supplier) |
| GET | `/api/material-certifications/{id}` | Hamta | X-API-Key |
| PUT | `/api/material-certifications/{id}` | Uppdatera | X-API-Key (supplier) |
| DELETE | `/api/material-certifications/{id}` | Ta bort | X-API-Key (supplier) |

**Certification-objekt:**
```json
{
  "certification": "GOTS",
  "certification_id": "CU-GOTS-12345",
  "valid_until": "2026-12-31"
}
```

---

### 17. MATERIAL SUPPLY CHAIN

| Metod | Endpoint | Beskrivning | Auth |
|-------|----------|-------------|------|
| GET | `/api/materials/{materialId}/supply-chain` | Lista supply chain | X-API-Key |
| POST | `/api/materials/{materialId}/supply-chain` | Lagg till steg | X-API-Key (supplier) |
| GET | `/api/supply-chain/{id}` | Hamta steg | X-API-Key |
| PUT | `/api/supply-chain/{id}` | Uppdatera steg | X-API-Key (supplier) |
| DELETE | `/api/supply-chain/{id}` | Ta bort steg | X-API-Key (supplier) |

**SupplyChain-objekt:**
```json
{
  "sequence": 1,
  "process_step": "spinning",
  "country": "IN",
  "facility_name": "Gujarat Spinning Mill",
  "facility_identifier": "GLN-123456"
}
```

---

### 18. DPP EXPORT

| Metod | Endpoint | Beskrivning | Auth |
|-------|----------|-------------|------|
| GET | `/api/products/{id}/dpp/preview` | Forhandsvisning av DPP | X-API-Key |
| GET | `/api/products/{id}/dpp/validate` | Validera DPP-data | X-API-Key |
| GET | `/api/products/{id}/dpp/export` | Exportera DPP | X-API-Key |

---

### 19. ADMIN API

Alla admin-endpoints kraver `X-Admin-Key` header.

#### Brands
| Metod | Endpoint | Beskrivning |
|-------|----------|-------------|
| GET | `/api/admin/brands` | Lista alla brands |
| POST | `/api/admin/brands` | Skapa brand |
| GET | `/api/admin/brands/{id}` | Hamta brand |
| PUT | `/api/admin/brands/{id}` | Uppdatera brand |
| DELETE | `/api/admin/brands/{id}` | Ta bort brand |
| POST | `/api/admin/brands/{id}/regenerate-key` | Ny API-nyckel |

#### Suppliers
| Metod | Endpoint | Beskrivning |
|-------|----------|-------------|
| GET | `/api/admin/suppliers` | Lista alla suppliers |
| POST | `/api/admin/suppliers` | Skapa supplier |
| GET | `/api/admin/suppliers/{id}` | Hamta supplier |
| PUT | `/api/admin/suppliers/{id}` | Uppdatera supplier |
| DELETE | `/api/admin/suppliers/{id}` | Ta bort supplier |
| POST | `/api/admin/suppliers/{id}/regenerate-key` | Ny API-nyckel |

#### Relationer
| Metod | Endpoint | Beskrivning |
|-------|----------|-------------|
| GET | `/api/admin/relations` | Lista relationer |
| POST | `/api/admin/relations` | Skapa relation |
| GET | `/api/admin/relations/{id}` | Hamta relation |
| PUT | `/api/admin/relations/{id}` | Uppdatera relation |
| DELETE | `/api/admin/relations/{id}` | Ta bort relation |

#### Statistik
| Metod | Endpoint | Beskrivning |
|-------|----------|-------------|
| GET | `/api/admin/stats` | Hamta systemstatistik |

---

## Datamodell (ER-oversikt)

```
brands (1) ----< brand_suppliers >---- (1) suppliers
  |                                          |
  |                                          |
  v                                          v
products (1) ----< batches >---- (1) suppliers
  |                  |
  |                  |--- batch_materials ---< factory_materials
  |                  |                            |
  |                  v                            |--- factory_material_compositions
  |               items                           |--- factory_material_certifications
  |                                               |--- factory_material_supply_chain
  |
  |--- product_components
  |--- product_variants
  |--- care_information (1:1)
  |--- compliance_info
  |--- product_certifications
  |--- circularity_info (1:1)
  |--- sustainability_info (1:1)
  |--- dpp_exports
```

---

## Tips for Windows-app (C# / WPF / WinUI)

### HttpClient-exempel (C#)

```csharp
using System.Net.Http;
using System.Net.Http.Json;

public class DppApiClient
{
    private readonly HttpClient _http;
    private readonly string _baseUrl;

    public DppApiClient(string baseUrl, string apiKey)
    {
        _baseUrl = baseUrl.TrimEnd('/');
        _http = new HttpClient();
        _http.DefaultRequestHeaders.Add("X-API-Key", apiKey);
    }

    // Lista produkter
    public async Task<ApiResponse<List<Product>>> GetProductsAsync()
    {
        return await _http.GetFromJsonAsync<ApiResponse<List<Product>>>(
            $"{_baseUrl}/api/products");
    }

    // Hamta en produkt
    public async Task<ApiResponse<Product>> GetProductAsync(int id)
    {
        return await _http.GetFromJsonAsync<ApiResponse<Product>>(
            $"{_baseUrl}/api/products/{id}");
    }

    // Skapa produkt
    public async Task<ApiResponse<Product>> CreateProductAsync(int brandId, Product product)
    {
        var response = await _http.PostAsJsonAsync(
            $"{_baseUrl}/api/brands/{brandId}/products", product);
        return await response.Content.ReadFromJsonAsync<ApiResponse<Product>>();
    }

    // Uppdatera produkt
    public async Task<ApiResponse<Product>> UpdateProductAsync(int id, Product product)
    {
        var response = await _http.PutAsJsonAsync(
            $"{_baseUrl}/api/products/{id}", product);
        return await response.Content.ReadFromJsonAsync<ApiResponse<Product>>();
    }

    // Ta bort produkt
    public async Task<ApiResponse<object>> DeleteProductAsync(int id)
    {
        var response = await _http.DeleteAsync($"{_baseUrl}/api/products/{id}");
        return await response.Content.ReadFromJsonAsync<ApiResponse<object>>();
    }

    // Hamta komplett DPP
    public async Task<ApiResponse<DppExport>> GetDppAsync(int productId)
    {
        return await _http.GetFromJsonAsync<ApiResponse<DppExport>>(
            $"{_baseUrl}/api/products/{productId}/dpp");
    }
}

// Response-wrapper
public class ApiResponse<T>
{
    public bool Success { get; set; }
    public T Data { get; set; }
    public string Error { get; set; }
}
```

### Rekommenderad arkitektur

```
DppApp/
  Models/           - Product.cs, Brand.cs, Supplier.cs, Batch.cs, Item.cs ...
  Services/
    DppApiClient.cs - HTTP-klient (som ovan)
    AuthService.cs  - Hantera API-nyckel
  ViewModels/       - MVVM ViewModels
  Views/            - XAML-vyer (WPF/WinUI)
```

### NuGet-paket att anvanda
- `System.Net.Http.Json` - JSON serialisering
- `CommunityToolkit.Mvvm` - MVVM-ramverk
- `Microsoft.Extensions.Http` - HttpClientFactory
