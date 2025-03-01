<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Producto extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'categoria_id',
        'unidad_medida_id',
        'precio_compra',
        'precio_venta',
        'stock_minimo',
        'stock_actual',
        'is_activo'
    ];

    protected $casts = [
        'precio_compra' => 'decimal:2',
        'precio_venta' => 'decimal:2',
        'stock_minimo' => 'decimal:2',
        'stock_actual' => 'decimal:2',
        'is_activo' => 'boolean'
    ];

    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }

    public function unidadMedida()
    {
        return $this->belongsTo(UnidadMedida::class);
    }

    public function detallesUnidad()
    {
        return $this->hasMany(DetalleUnidadProducto::class);
    }

    public function movimientosInventario()
    {
        return $this->hasMany(MovimientoInventario::class);
    }

    public function historialCostos()
    {
        return $this->hasMany(HistorialCosto::class);
    }

    public function ventaDetalles()
    {
        return $this->hasMany(VentaDetalle::class);
    }

    public function compraDetalles()
    {
        return $this->hasMany(CompraDetalle::class);
    }

    public function actualizarStock($cantidad, $tipo, $datos = [])
    {
        $movimiento = [
            'producto_id' => $this->id,
            'tipo' => $tipo,
            'cantidad' => $cantidad,
            'stock_anterior' => $this->stock_actual,
        ];

        switch ($tipo) {
            case 'ENTRADA':
            case 'AJUSTE_ENTRADA':
                $this->stock_actual += $cantidad;
                break;
            case 'SALIDA':
            case 'AJUSTE_SALIDA':
                if ($this->stock_actual < $cantidad) {
                    throw new \Exception("Stock insuficiente para el producto {$this->nombre}");
                }
                $this->stock_actual -= $cantidad;
                break;
            case 'DEVOLUCION':
                $this->stock_actual += $cantidad;
                break;
            default:
                throw new \Exception("Tipo de movimiento no válido");
        }

        $movimiento['stock_actual'] = $this->stock_actual;
        $movimiento = array_merge($movimiento, $datos);

        $this->save();
        $this->movimientosInventario()->create($movimiento);

        return $this;
    }

    public function actualizarPrecioCompra($nuevoPrecio)
    {
        if ($nuevoPrecio != $this->precio_compra) {
            $this->historialCostos()->create([
                'costo_anterior' => $this->precio_compra,
                'costo_nuevo' => $nuevoPrecio
            ]);

            $this->precio_compra = $nuevoPrecio;
            $this->save();
        }

        return $this;
    }

    public function actualizarPrecioVenta($nuevoPrecio)
    {
        $this->precio_venta = $nuevoPrecio;
        $this->save();

        return $this;
    }

    public function getItbisAttribute()
    {
        switch ($this->tipo_itbis) {
            case 1:
                return 0.18;
            case 2:
                return 0.16;
            case 3:
                return 0;
            default:
                return 0.18;
        }
    }

    public function scopeActivos($query)
    {
        return $query->where('is_activo', true);
    }

    public function scopeBajoStock($query)
    {
        return $query->whereRaw('stock_actual <= stock_minimo');
    }
} 