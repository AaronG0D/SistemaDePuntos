// Simplified PDF export using browser's print functionality and HTML generation

interface Assignment {
    id: number;
    puntos: number;
    fecha_asignacion: string;
    comentario?: string;
    estudiante: {
        nombres: string;
        apellidos: string;
        codigo_estudiante: string;
        curso?: {
            nombre: string;
        };
        paralelo?: {
            nombre: string;
        };
    };
    materia: {
        nombre: string;
    };
    periodo: {
        nombre: string;
    };
}

interface Teacher {
    nombres: string;
    apellidos: string;
}

interface ReportData {
    teacher: Teacher;
    assignments: Assignment[];
    totalPoints: number;
    totalStudents: number;
    averagePoints: number;
    selectedFilters: {
        subject?: string;
        course?: string;
        period?: string;
    };
}

export class PDFExportService {
    exportReportToPDF(data: ReportData): void {
        const htmlContent = this.generateReportHTML(data);
        this.printHTML(htmlContent, `reporte_puntos_${new Date().toISOString().split('T')[0]}`);
    }

    exportStudentListToPDF(students: any[], teacher: Teacher, filters: any): void {
        const htmlContent = this.generateStudentListHTML(students, teacher, filters);
        this.printHTML(htmlContent, `lista_estudiantes_${new Date().toISOString().split('T')[0]}`);
    }

    private generateReportHTML(data: ReportData): string {
        return `
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <title>Reporte de Puntos por Materia</title>
                <style>
                    @media print {
                        body { margin: 0; }
                        .no-print { display: none; }
                    }
                    body {
                        font-family: Arial, sans-serif;
                        margin: 20px;
                        color: #333;
                    }
                    .header {
                        text-align: center;
                        border-bottom: 2px solid #007bff;
                        padding-bottom: 20px;
                        margin-bottom: 30px;
                    }
                    .header h1 {
                        color: #007bff;
                        margin: 0;
                        font-size: 24px;
                    }
                    .header h2 {
                        color: #6c757d;
                        margin: 10px 0;
                        font-size: 18px;
                    }
                    .info-section {
                        background: #f8f9fa;
                        padding: 15px;
                        border-radius: 5px;
                        margin-bottom: 20px;
                    }
                    .stats-grid {
                        display: grid;
                        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                        gap: 15px;
                        margin-bottom: 30px;
                    }
                    .stat-card {
                        background: white;
                        border: 1px solid #dee2e6;
                        border-radius: 5px;
                        padding: 15px;
                        text-align: center;
                    }
                    .stat-value {
                        font-size: 24px;
                        font-weight: bold;
                        color: #007bff;
                    }
                    .stat-label {
                        color: #6c757d;
                        font-size: 14px;
                    }
                    table {
                        width: 100%;
                        border-collapse: collapse;
                        margin-top: 20px;
                    }
                    th, td {
                        border: 1px solid #dee2e6;
                        padding: 8px;
                        text-align: left;
                        font-size: 12px;
                    }
                    th {
                        background-color: #007bff;
                        color: white;
                        font-weight: bold;
                    }
                    tr:nth-child(even) {
                        background-color: #f8f9fa;
                    }
                    .footer {
                        margin-top: 30px;
                        text-align: center;
                        font-size: 12px;
                        color: #6c757d;
                        border-top: 1px solid #dee2e6;
                        padding-top: 15px;
                    }
                </style>
            </head>
            <body>
                <div class="header">
                    <h1>🌱 Sistema de Puntos Ecológicos</h1>
                    <h2>Reporte de Asignación de Puntos por Materia</h2>
                    <p><strong>Docente:</strong> ${data.teacher.nombres} ${data.teacher.apellidos}</p>
                    <p><strong>Fecha de generación:</strong> ${new Date().toLocaleDateString('es-ES')}</p>
                </div>

                <div class="info-section">
                    <h3>Filtros Aplicados:</h3>
                    <ul>
                        <li><strong>Materia:</strong> ${data.selectedFilters.subject || 'Todas'}</li>
                        <li><strong>Curso:</strong> ${data.selectedFilters.course || 'Todos'}</li>
                        <li><strong>Período:</strong> ${data.selectedFilters.period || 'Todos'}</li>
                    </ul>
                </div>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-value">${data.totalPoints}</div>
                        <div class="stat-label">Puntos Totales</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">${data.totalStudents}</div>
                        <div class="stat-label">Estudiantes</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">${data.averagePoints}</div>
                        <div class="stat-label">Promedio</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">${data.assignments.length}</div>
                        <div class="stat-label">Asignaciones</div>
                    </div>
                </div>

                <h3>Detalle de Asignaciones</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Estudiante</th>
                            <th>Código</th>
                            <th>Materia</th>
                            <th>Curso</th>
                            <th>Período</th>
                            <th>Puntos</th>
                            <th>Fecha</th>
                            <th>Comentario</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${data.assignments.map(assignment => `
                            <tr>
                                <td>${assignment.estudiante.apellidos}, ${assignment.estudiante.nombres}</td>
                                <td>${assignment.estudiante.codigo_estudiante}</td>
                                <td>${assignment.materia.nombre}</td>
                                <td>${assignment.estudiante.curso?.nombre || 'N/A'} "${assignment.estudiante.paralelo?.nombre || 'N/A'}"</td>
                                <td>${assignment.periodo.nombre}</td>
                                <td style="text-align: center; font-weight: bold; color: #28a745;">${assignment.puntos}</td>
                                <td>${new Date(assignment.fecha_asignacion).toLocaleDateString('es-ES')}</td>
                                <td>${assignment.comentario || 'Sin comentario'}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>

                <div class="footer">
                    <p>Sistema de Puntos Ecológicos - Reporte generado automáticamente</p>
                    <p>Generado el ${new Date().toLocaleString('es-ES')}</p>
                </div>
            </body>
            </html>
        `;
    }

    private generateStudentListHTML(students: any[], teacher: Teacher, filters: any): string {
        return `
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <title>Lista de Estudiantes</title>
                <style>
                    @media print {
                        body { margin: 0; }
                        .no-print { display: none; }
                    }
                    body {
                        font-family: Arial, sans-serif;
                        margin: 20px;
                        color: #333;
                    }
                    .header {
                        text-align: center;
                        border-bottom: 2px solid #17a2b8;
                        padding-bottom: 20px;
                        margin-bottom: 30px;
                    }
                    .header h1 {
                        color: #17a2b8;
                        margin: 0;
                        font-size: 24px;
                    }
                    table {
                        width: 100%;
                        border-collapse: collapse;
                        margin-top: 20px;
                    }
                    th, td {
                        border: 1px solid #dee2e6;
                        padding: 10px;
                        text-align: left;
                    }
                    th {
                        background-color: #17a2b8;
                        color: white;
                        font-weight: bold;
                    }
                    tr:nth-child(even) {
                        background-color: #f8f9fa;
                    }
                    .performance-excellent { color: #28a745; font-weight: bold; }
                    .performance-good { color: #007bff; font-weight: bold; }
                    .performance-regular { color: #ffc107; font-weight: bold; }
                    .performance-needs-improvement { color: #dc3545; font-weight: bold; }
                    .footer {
                        margin-top: 30px;
                        text-align: center;
                        font-size: 12px;
                        color: #6c757d;
                        border-top: 1px solid #dee2e6;
                        padding-top: 15px;
                    }
                </style>
            </head>
            <body>
                <div class="header">
                    <h1>👥 Lista de Estudiantes</h1>
                    <p><strong>Docente:</strong> ${teacher.nombres} ${teacher.apellidos}</p>
                    <p><strong>Fecha:</strong> ${new Date().toLocaleDateString('es-ES')}</p>
                    <p><strong>Total de estudiantes:</strong> ${students.length}</p>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Estudiante</th>
                            <th>Código</th>
                            <th>Curso</th>
                            <th>Puntos Totales</th>
                            <th>Promedio</th>
                            <th>Rendimiento</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${students.map((student, index) => `
                            <tr>
                                <td style="text-align: center;">${index + 1}</td>
                                <td>${student.apellidos}, ${student.nombres}</td>
                                <td>${student.codigo_estudiante}</td>
                                <td>${student.curso?.nombre || 'N/A'} "${student.paralelo?.nombre || 'N/A'}"</td>
                                <td style="text-align: center; font-weight: bold; color: #17a2b8;">${student.total_puntos}</td>
                                <td style="text-align: center;">${student.promedio}</td>
                                <td class="performance-${this.getPerformanceClass(student.promedio)}">${this.getPerformanceLabel(student.promedio)}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>

                <div class="footer">
                    <p>Sistema de Puntos Ecológicos - Lista generada automáticamente</p>
                    <p>Generado el ${new Date().toLocaleString('es-ES')}</p>
                </div>
            </body>
            </html>
        `;
    }

    private printHTML(htmlContent: string, filename: string): void {
        const printWindow = window.open('', '_blank');
        if (printWindow) {
            printWindow.document.write(htmlContent);
            printWindow.document.close();
            
            // Wait for content to load then print
            printWindow.onload = () => {
                setTimeout(() => {
                    printWindow.print();
                }, 500);
            };
        } else {
            // Fallback: create downloadable HTML file
            const blob = new Blob([htmlContent], { type: 'text/html' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `${filename}.html`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }
    }

    private getPerformanceLabel(promedio: number): string {
        if (promedio >= 80) return 'Excelente';
        if (promedio >= 60) return 'Bueno';
        if (promedio >= 40) return 'Regular';
        return 'Necesita mejora';
    }

    private getPerformanceClass(promedio: number): string {
        if (promedio >= 80) return 'excellent';
        if (promedio >= 60) return 'good';
        if (promedio >= 40) return 'regular';
        return 'needs-improvement';
    }
}

// Export singleton instance
export const pdfExportService = new PDFExportService();
