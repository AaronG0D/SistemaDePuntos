#!/usr/bin/env python3
"""
Generador de Códigos QR para Sistema de Reciclaje
Optimizado para Raspberry Pi con detección de cámara
"""

import qrcode
import mysql.connector
import unicodedata
import re
from reportlab.pdfgen import canvas
from reportlab.lib.pagesizes import A4
from reportlab.lib.units import mm
from PIL import Image
import os
import sys
from datetime import datetime

# Configuración de la base de datos
DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': 'pi1234',
    'database': 'sistemaReciclajeDB',
    'charset': 'utf8mb4'
}

# Configuración del QR
QR_SIZE = 40 * mm
MARGIN_X = 20 * mm
MARGIN_Y = 20 * mm
GAP_X = 10 * mm
GAP_Y = 20 * mm

# Configuración del logo
LOGO_PATH = "/home/pi/Documents/logo.png"
LOGO_SIZE_RATIO = 0.2

def generar_codigo_qr_con_logo(texto, archivo_png):
    """
    Genera un código QR con logo integrado
    Optimizado para detección con Raspberry Pi
    """
    try:
        # Crear QR con corrección de errores alta para mejor detección
        qr = qrcode.QRCode(
            version=1,
            error_correction=qrcode.constants.ERROR_CORRECT_H,
            box_size=10,
            border=4,
        )
        qr.add_data(texto)
        qr.make(fit=True)
        
        # Crear imagen con colores de alto contraste
        img = qr.make_image(
            fill_color="black",
            back_color="white"
        ).convert('RGB')

        # Agregar logo si existe
        if os.path.exists(LOGO_PATH):
            try:
                logo = Image.open(LOGO_PATH)
                logo_size = int(img.size[0] * LOGO_SIZE_RATIO)
                logo = logo.resize((logo_size, logo_size), Image.LANCZOS)
                
                # Posicionar logo en el centro
                pos = ((img.size[0] - logo_size) // 2, (img.size[1] - logo_size) // 2)
                
                # Crear máscara para el logo
                if logo.mode == 'RGBA':
                    img.paste(logo, pos, logo)
                else:
                    img.paste(logo, pos)
                    
                print(f"[OK] QR generado con logo: {archivo_png}")
            except Exception as e:
                print(f"[WARNING] Error procesando logo: {e}")
                print(f"[OK] QR generado sin logo: {archivo_png}")
        else:
            print(f"[WARNING] Logo no encontrado en: {LOGO_PATH}")
            print(f"[OK] QR generado sin logo: {archivo_png}")

        # Guardar imagen
        img.save(archivo_png, 'PNG', optimize=True)
        return True
        
    except Exception as e:
        print(f"[ERROR] Error generando QR: {e}")
        return False

def obtener_estudiantes():
    """
    Obtiene la lista de estudiantes desde la base de datos
    """
    estudiantes = []
    try:
        con = mysql.connector.connect(**DB_CONFIG)
        cur = con.cursor()
        
        # Query optimizada para obtener datos necesarios
        cur.execute("""
            SELECT 
                u.id,
                CONCAT(u.nombres, ' ', u.primerApellido, ' ', u.segundoApellido) AS nombre_completo,
                u.qr_codigo,
                c.nombre as curso_nombre,
                p.nombre as paralelo_nombre
            FROM usuario u
            LEFT JOIN estudiante e ON u.id = e.idUser
            LEFT JOIN curso_paralelo cp ON e.idCursoParalelo = cp.idCursoParalelo
            LEFT JOIN curso c ON cp.idCurso = c.idCurso
            LEFT JOIN paralelo p ON cp.idParalelo = p.idParalelo
            WHERE u.rol = 'estudiante'
            ORDER BY u.primerApellido, u.segundoApellido, u.nombres
        """)
        
        for row in cur.fetchall():
            id_user, nombre, codigo, curso, paralelo = row
            estudiantes.append({
                'id': id_user,
                'nombre': nombre.strip(),
                'codigo': codigo,
                'curso': curso or 'Sin curso',
                'paralelo': paralelo or 'Sin paralelo'
            })
            
        cur.close()
        con.close()
        
        print(f"[OK] {len(estudiantes)} estudiantes encontrados")
        return estudiantes
        
    except mysql.connector.Error as err:
        print(f"[MySQL ERROR]: {err}")
        return []
    except Exception as e:
        print(f"[ERROR] Error obteniendo estudiantes: {e}")
        return []

def normalizar_nombre(nombre):
    """
    Normaliza el nombre para usar como nombre de archivo
    """
    # Normalizar caracteres especiales
    nombre = unicodedata.normalize('NFKD', nombre).encode('ASCII', 'ignore').decode('utf-8')
    # Reemplazar espacios y caracteres especiales
    nombre = re.sub(r'\s+', '_', nombre.lower())
    nombre = re.sub(r'[^a-z0-9_]', '', nombre)
    return nombre

def generar_pdf(info_qrs, archivo_pdf):
    """
    Genera un PDF con los códigos QR organizados para impresión
    """
    try:
        c = canvas.Canvas(archivo_pdf, pagesize=A4)
        ancho, alto = A4
        
        x = MARGIN_X
        y = alto - MARGIN_Y - QR_SIZE
        
        qr_count = 0
        page_count = 1
        
        for info in info_qrs:
            nombre = info['nombre']
            codigo = info['codigo']
            archivo_png = info['archivo_png']
            curso = info['curso']
            paralelo = info['paralelo']
            
            if not os.path.exists(archivo_png):
                print(f"[WARNING] Imagen no encontrada: {archivo_png}")
                continue

            # Dibujar QR
            c.drawImage(archivo_png, x, y, QR_SIZE, QR_SIZE)
            
            # Dibujar información del estudiante
            c.setFont("Helvetica", 8)
            c.drawString(x, y - 10, f"{nombre}")
            c.drawString(x, y - 20, f"{codigo}")
            c.drawString(x, y - 30, f"{curso} - {paralelo}")

            # Mover a la siguiente posición
            x += QR_SIZE + GAP_X
            qr_count += 1
            
            # Nueva fila si se llena la actual
            if x + QR_SIZE + MARGIN_X > ancho:
                x = MARGIN_X
                y -= QR_SIZE + GAP_Y + 30
                
            # Nueva página si se llena la actual
            if y < MARGIN_Y:
                c.showPage()
                page_count += 1
                x = MARGIN_X
                y = alto - MARGIN_Y - QR_SIZE

        c.save()
        print(f"[OK] PDF generado: {archivo_pdf}")
        print(f"[INFO] {qr_count} códigos QR en {page_count} páginas")
        return True
        
    except Exception as e:
        print(f"[ERROR] Error generando PDF: {e}")
        return False

def limpiar_archivos_antiguos(dias=7):
    """
    Limpia archivos QR antiguos para ahorrar espacio
    """
    try:
        archivos_eliminados = 0
        tiempo_limite = datetime.now().timestamp() - (dias * 24 * 60 * 60)
        
        for archivo in os.listdir('.'):
            if archivo.startswith('qr_') and archivo.endswith('.png'):
                ruta_archivo = os.path.join('.', archivo)
                if os.path.getmtime(ruta_archivo) < tiempo_limite:
                    os.remove(ruta_archivo)
                    archivos_eliminados += 1
                    
        print(f"[OK] {archivos_eliminados} archivos antiguos eliminados")
        return True
        
    except Exception as e:
        print(f"[ERROR] Error limpiando archivos: {e}")
        return False

def main():
    """
    Función principal del programa
    """
    print("=== Generador de Códigos QR para Sistema de Reciclaje ===")
    print(f"Fecha: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")
    print()
    
    # Obtener estudiantes
    estudiantes = obtener_estudiantes()
    if not estudiantes:
        print("[ERROR] No se encontraron estudiantes")
        sys.exit(1)
    
    # Generar QRs
    info_qrs = []
    qrs_generados = 0
    qrs_existentes = 0
    
    for estudiante in estudiantes:
        nombre_limpio = normalizar_nombre(estudiante['nombre'])
        archivo_png = f"qr_{nombre_limpio}.png"
        
        if not os.path.exists(archivo_png):
            if generar_codigo_qr_con_logo(estudiante['codigo'], archivo_png):
                qrs_generados += 1
            else:
                print(f"[ERROR] No se pudo generar QR para: {estudiante['nombre']}")
                continue
        else:
            qrs_existentes += 1
            print(f"[INFO] QR ya existe: {archivo_png}")
            
        info_qrs.append({
            'nombre': estudiante['nombre'],
            'codigo': estudiante['codigo'],
            'archivo_png': archivo_png,
            'curso': estudiante['curso'],
            'paralelo': estudiante['paralelo']
        })
    
    print(f"\n[RESUMEN]")
    print(f"- QRs generados: {qrs_generados}")
    print(f"- QRs existentes: {qrs_existentes}")
    print(f"- Total procesados: {len(info_qrs)}")
    
    # Generar PDF
    if info_qrs:
        archivo_pdf = f"qr_estudiantes_{datetime.now().strftime('%Y%m%d_%H%M%S')}.pdf"
        if generar_pdf(info_qrs, archivo_pdf):
            print(f"[OK] PDF generado exitosamente: {archivo_pdf}")
        else:
            print("[ERROR] No se pudo generar el PDF")
    
    # Limpiar archivos antiguos
    limpiar_archivos_antiguos(7)
    
    print("\n=== Proceso completado ===")

if __name__ == "__main__":
    main()
