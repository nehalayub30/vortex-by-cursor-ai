.vortex-insight-section {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    padding: 20px;
    margin-bottom: 20px;
}

.vortex-insight-section h3 {
    color: #333;
    font-size: 18px;
    margin-top: 0;
    margin-bottom: 15px;
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
}

.vortex-insight-chart {
    height: 300px;
    margin-bottom: 20px;
}

.vortex-insight-list {
    margin-bottom: 15px;
}

.vortex-insight-list ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.vortex-insight-item {
    margin-bottom: 15px;
    padding: 15px;
    background: #f9f9f9;
    border-radius: 6px;
}

.vortex-insight-item h4 {
    margin-top: 0;
    margin-bottom: 10px;
    color: #555;
    font-size: 16px;
}

.vortex-insight-value {
    display: flex;
    align-items: center;
    margin-top: 10px;
    font-weight: bold;
}

.vortex-insight-value .value {
    font-size: 18px;
    color: #333;
}

.vortex-insight-value .trend {
    margin-left: 10px;
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 14px;
}

.vortex-insight-value .trend-up {
    background: #e6f7ef;
    color: #0c7c59;
}

.vortex-insight-value .trend-down {
    background: #fde8e8;
    color: #c81e1e;
}

.vortex-insight-value .trend-neutral {
    background: #f0f1f2;
    color: #666;
}

.vortex-insight-summary {
    background: #f2f7ff;
    padding: 15px;
    border-radius: 6px;
    margin-bottom: 15px;
}

.vortex-insight-footer {
    display: flex;
    justify-content: space-between;
    font-size: 12px;
    color: #888;
    border-top: 1px solid #eee;
    padding-top: 10px;
}

.vortex-no-data {
    padding: 20px;
    text-align: center;
    color: #666;
    background: #f5f5f5;
    border-radius: 4px;
}

/* Agent-specific styling */
.vortex-agent-cloe h3 {
    border-color: #4c6ef5;
}

.vortex-agent-business h3 {
    border-color: #12b886;
}

.vortex-agent-huraii h3 {
    border-color: #f76707;
}

.vortex-agent-thorius h3 {
    border-color: #9775fa;
}

/* Responsive styles */
@media (max-width: 768px) {
    .vortex-insight-footer {
        flex-direction: column;
        gap: 5px;
    }
    
    .vortex-insight-chart {
        height: 250px;
    }
} 