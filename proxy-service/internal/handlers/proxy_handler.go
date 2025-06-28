package handlers

import (
	"net/http"

	"proxy-service/config"
	"proxy-service/internal/proxy"
	"proxy-service/internal/storage"

	"github.com/gin-gonic/gin"
)

type ProxyHandler struct {
	manager *proxy.Manager
	storage storage.Storage
	config  *config.Config
}

func NewProxyHandler(manager *proxy.Manager, storage storage.Storage, cfg *config.Config) *ProxyHandler {
	return &ProxyHandler{
		manager: manager,
		storage: storage,
		config:  cfg,
	}
}

func (h *ProxyHandler) HealthCheck(c *gin.Context) {
	c.JSON(http.StatusOK, gin.H{
		"status":   "healthy",
		"service":  "proxy-manager",
		"base_url": h.config.BaseURL,
	})
}

func (h *ProxyHandler) GetProxy(c *gin.Context) {
	proxy, err := h.manager.GetProxy()
	if err != nil {
		c.JSON(http.StatusNotFound, gin.H{
			"error": err.Error(),
		})
		return
	}

	c.JSON(http.StatusOK, gin.H{
		"success": true,
		"data":    proxy,
	})
}

func (h *ProxyHandler) RotateProxy(c *gin.Context) {
	proxy, err := h.manager.RotateProxy()
	if err != nil {
		c.JSON(http.StatusNotFound, gin.H{
			"error": err.Error(),
		})
		return
	}

	c.JSON(http.StatusOK, gin.H{
		"success": true,
		"data":    proxy,
	})
}

func (h *ProxyHandler) ListProxies(c *gin.Context) {
	proxies := h.manager.ListProxies()

	c.JSON(http.StatusOK, gin.H{
		"success": true,
		"data":    proxies,
		"count":   len(proxies),
	})
}

func (h *ProxyHandler) AddProxy(c *gin.Context) {
	var proxyData struct {
		Host     string `json:"host" binding:"required"`
		Port     int    `json:"port" binding:"required"`
		Username string `json:"username"`
		Password string `json:"password"`
		Protocol string `json:"protocol" binding:"required"`
	}

	if err := c.ShouldBindJSON(&proxyData); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{
			"error": err.Error(),
		})
		return
	}

	proxy := &proxy.Proxy{
		Host:     proxyData.Host,
		Port:     proxyData.Port,
		Username: proxyData.Username,
		Password: proxyData.Password,
		Protocol: proxyData.Protocol,
	}

	if err := h.manager.AddProxy(proxy); err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{
			"error": err.Error(),
		})
		return
	}

	c.JSON(http.StatusCreated, gin.H{
		"success": true,
		"data":    proxy,
	})
}

func (h *ProxyHandler) RemoveProxy(c *gin.Context) {
	id := c.Param("id")

	if err := h.manager.RemoveProxy(id); err != nil {
		c.JSON(http.StatusNotFound, gin.H{
			"error": err.Error(),
		})
		return
	}

	c.JSON(http.StatusOK, gin.H{
		"success": true,
		"message": "Proxy removed successfully",
	})
}

func (h *ProxyHandler) GetStats(c *gin.Context) {
	stats := h.manager.GetStats()

	c.JSON(http.StatusOK, gin.H{
		"success": true,
		"data":    stats,
	})
}

func (h *ProxyHandler) GetConfig(c *gin.Context) {
	c.JSON(http.StatusOK, gin.H{
		"success": true,
		"data": gin.H{
			"base_url":       h.config.BaseURL,
			"port":           h.config.Port,
			"proxy_timeout":  h.config.ProxyTimeout,
			"max_retries":    h.config.MaxRetries,
			"rotation_delay": h.config.RotationDelay,
		},
	})
}
