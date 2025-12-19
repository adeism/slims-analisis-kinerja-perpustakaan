<style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: #f5f5f5;
        margin: 0;
        padding: 0;
        color: #333;
        line-height: 1.6;
    }

    .info-popup {
        display: none;
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: white;
        padding: 25px;
        border-radius: 10px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
        max-width: 600px;
        z-index: 10000;
    }

    .info-popup.active {
        display: block;
    }

    .popup-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 9999;
    }

    .popup-overlay.active {
        display: block;
    }

    .popup-close {
        position: absolute;
        top: 10px;
        right: 15px;
        font-size: 28px;
        font-weight: bold;
        color: #999;
        cursor: pointer;
        border: none;
        background: none;
    }

    .popup-close:hover {
        color: #333;
    }

    .filter-form-wrapper {
        background: white;
        padding: 20px;
        margin-bottom: 0;
        border-radius: 0;
    }

    .inline-form {
        display: flex;
        align-items: center;
        gap: 15px;
        flex-wrap: wrap;
    }

    .inline-form select {
        padding: 8px 12px;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
    }

    .inline-form label {
        display: flex;
        align-items: center;
        gap: 5px;
        margin: 0;
    }

    @media print {
        .filter-form-wrapper {
            display: none !important;
        }

        * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        body {
            margin: 0;
            padding: 10px;
            background: white !important;
            font-size: 10pt;
        }

        .non-printable {
            display: none !important;
        }

        .container {
            box-shadow: none !important;
            border-radius: 0 !important;
            padding: 0 !important;
        }

        h2 {
            font-size: 14pt;
            margin: 0 0 10px 0;
            padding: 10px 0;
            page-break-after: avoid;
        }

        .indicator-box {
            page-break-inside: avoid;
            margin: 10px 0;
            padding: 10px;
        }

        .chart-wrapper {
            page-break-inside: avoid;
        }

        table {
            font-size: 9pt;
            page-break-inside: avoid;
        }

        th,
        td {
            padding: 5px !important;
        }
    }

    .container {
        max-width: 100%;
        margin: 0;
        background: #fff;
        padding: 20px;
        border-bottom: 1px solid #e0e0e0;
    }

    .header-section {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 1px solid #e0e0e0;
    }

    h2 {
        color: #333;
        margin: 0;
        font-size: 24px;
        font-weight: 600;
    }

    .content-area {
        background: #f5f5f5;
        padding: 20px;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin: 25px 0;
    }

    .stat-card {
        background: #4A90E2;
        color: white;
        padding: 25px;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    }

    .stat-card h3 {
        margin: 0 0 10px 0;
        font-size: 14px;
        opacity: 0.9;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .stat-value {
        font-size: 36px;
        font-weight: bold;
        margin: 10px 0;
    }

    .stat-label {
        font-size: 13px;
        opacity: 0.8;
    }

    .indicator-box {
        background: white;
        border-radius: 8px;
        padding: 20px;
        margin: 0 0 20px 0;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .indicator-box:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    }

    .indicator-title {
        font-size: 16px;
        font-weight: 600;
        color: #333;
        margin-bottom: 15px;
        padding-bottom: 15px;
        border-bottom: 1px solid #eee;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .indicator-badge {
        background: #4A90E2;
        color: white;
        padding: 4px 10px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 600;
    }

    .indicator-desc {
        font-size: 13px;
        color: #666;
        margin-bottom: 15px;
        line-height: 1.6;
        padding: 12px;
        background: #e7f3ff;
        border-left: 4px solid #4A90E2;
        border-radius: 4px;
    }

    .chart-container {
        position: relative;
        margin: 25px 0;
        padding: 20px;
        background: #f8fafc;
        border-radius: 10px;
    }

    .chart-wrapper {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin: 15px 0;
    }

    .chart-box {
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .chart-box:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    }

    .chart-box h4 {
        color: #333;
        margin: 0 0 15px 0;
        font-size: 16px;
        font-weight: 600;
        padding-bottom: 15px;
        border-bottom: 1px solid #eee;
    }

    .bar-chart {
        display: flex;
        flex-direction: column;
        gap: 10px;
        padding: 10px 0;
    }

    .bar-item {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .bar-label {
        min-width: 100px;
        font-size: 13px;
        font-weight: 600;
        color: #2d3748;
    }

    .bar-container {
        flex: 1;
        background: #f0f0f0;
        border-radius: 6px;
        height: 32px;
        position: relative;
        overflow: hidden;
    }

    .bar-fill {
        height: 100%;
        background: #4A90E2;
        border-radius: 6px;
        transition: width 1s ease-out;
        animation: fillBar 1.5s ease-out;
    }

    @keyframes fillBar {
        from {
            width: 0;
        }
    }

    .bar-value {
        min-width: 90px;
        text-align: right;
        font-weight: 600;
        color: #4A90E2;
        font-size: 13px;
    }

    .progress-ring {
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 30px;
    }

    .ring-container {
        position: relative;
        width: 200px;
        height: 200px;
    }

    .ring-background {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        background: conic-gradient(#4A90E2 0deg,
                #4A90E2 var(--percentage),
                #f0f0f0 var(--percentage),
                #f0f0f0 360deg);
        display: flex;
        align-items: center;
        justify-content: center;
        animation: rotateRing 1.5s ease-out;
    }

    @keyframes rotateRing {
        from {
            transform: rotate(0deg);
        }

        to {
            transform: rotate(360deg);
        }
    }

    .ring-inner {
        width: 150px;
        height: 150px;
        border-radius: 50%;
        background: white;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .ring-value {
        font-size: 32px;
        font-weight: bold;
        color: #4A90E2;
    }

    .ring-label {
        font-size: 12px;
        color: #64748b;
        margin-top: 5px;
    }

    .comparison-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
        gap: 10px;
        margin: 12px 0;
    }

    .comparison-item {
        text-align: center;
        padding: 15px;
        background: white;
        border-radius: 8px;
        border: 1px solid #e0e0e0;
        transition: all 0.3s ease;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .comparison-item:hover {
        transform: translateY(-2px);
        border-color: #4A90E2;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    }

    .comparison-value {
        font-size: 24px;
        font-weight: bold;
        color: #4A90E2;
        margin: 8px 0;
    }

    .comparison-label {
        font-size: 12px;
        color: #64748b;
        font-weight: 600;
    }

    .metric-bars {
        display: flex;
        flex-direction: column;
        gap: 20px;
        padding: 20px 0;
    }

    .metric-bar-item {
        position: relative;
    }

    .metric-bar-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 8px;
    }

    .metric-bar-title {
        font-size: 14px;
        font-weight: 600;
        color: #2d3748;
    }

    .metric-bar-percentage {
        font-size: 14px;
        font-weight: bold;
        color: #4A90E2;
    }

    .metric-bar-track {
        height: 25px;
        background: #e2e8f0;
        border-radius: 12px;
        overflow: hidden;
        position: relative;
    }

    .metric-bar-progress {
        height: 100%;
        background: #4A90E2;
        border-radius: 12px;
        transition: width 1.2s ease-out;
        animation: slideIn 1.2s ease-out;
    }

    @keyframes slideIn {
        from {
            width: 0;
        }
    }

    .donut-chart {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 20px;
    }

    .donut-ring {
        width: 180px;
        height: 180px;
        border-radius: 50%;
        background: conic-gradient(from 0deg,
                #4A90E2 0deg,
                #4A90E2 calc(var(--value1) * 3.6deg),
                #e0e0e0 calc(var(--value1) * 3.6deg),
                #e0e0e0 360deg);
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        animation: spinDonut 1.5s ease-out;
    }

    @keyframes spinDonut {
        from {
            transform: rotate(0deg);
        }

        to {
            transform: rotate(360deg);
        }
    }

    .donut-hole {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        background: white;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }

    .donut-center-value {
        font-size: 24px;
        font-weight: bold;
        color: #4A90E2;
    }

    .donut-center-label {
        font-size: 11px;
        color: #64748b;
    }

    .donut-legend {
        display: flex;
        gap: 20px;
        margin-top: 20px;
        flex-wrap: wrap;
        justify-content: center;
    }

    .legend-item {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .legend-color {
        width: 16px;
        height: 16px;
        border-radius: 4px;
    }

    .legend-text {
        font-size: 13px;
        color: #2d3748;
    }

    .legend-value {
        font-weight: bold;
        color: #4A90E2;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin: 10px 0;
        background: white;
        border-radius: 6px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    th {
        background: #f9f9f9;
        color: #333;
        padding: 10px;
        text-align: left;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 11px;
        letter-spacing: 0.5px;
        border-bottom: 2px solid #e0e0e0;
    }

    td {
        padding: 8px 10px;
        border-bottom: 1px solid #e2e8f0;
        color: #2d3748;
        font-size: 13px;
    }

    tr:last-child td {
        border-bottom: none;
    }

    tr:hover {
        background: #f8fafc;
    }

    td:first-child {
        font-weight: 600;
        color: #4A90E2;
    }

    .error-box {
        background: #fee2e2;
        border: 2px solid #fca5a5;
        padding: 12px 15px;
        margin: 10px 0;
        border-radius: 8px;
        color: #991b1b;
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
    }

    .error-box::before {
        content: "⚠️";
        font-size: 20px;
    }

    .info-box {
        background: #e3f2fd;
        border: 1px solid #90caf9;
        padding: 12px 15px;
        margin: 10px 0;
        border-radius: 8px;
        color: #1976d2;
        display: flex;
        align-items: flex-start;
        gap: 8px;
        font-size: 13px;
    }

    .info-box::before {
        content: "ℹ️";
        font-size: 20px;
        flex-shrink: 0;
    }

    .btn-print {
        padding: 8px 16px;
        background: #4A90E2;
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 6px;
        transition: background 0.2s ease;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .btn-print:hover {
        background: #357ABD;
    }

    .btn-print:active {
        background: #2868A8;
    }

    @media (max-width: 768px) {
        .chart-wrapper {
            grid-template-columns: 1fr;
        }

        .stats-grid {
            grid-template-columns: 1fr;
        }
    }
</style>