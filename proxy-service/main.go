package main

import (
	"log"
	"net/http"
	"os"
	"os/signal"
	"syscall"

	"proxy-service/config"
	"proxy-service/internal/handlers"
	"proxy-service/internal/proxy"
	"proxy-service/internal/storage"

	"github.com/gin-gonic/gin"
)

func main() {
	cfg := config.Load()

	log.Printf("Configuration loaded - Base URL: %s, Port: %s", cfg.BaseURL, cfg.Port)

	proxyManager := proxy.NewManager(cfg)
	storage := storage.NewMemoryStorage()

	handler := handlers.NewProxyHandler(proxyManager, storage, cfg)

	router := gin.Default()

	router.GET("/health", handler.HealthCheck)
	router.GET("/config", handler.GetConfig)
	router.GET("/proxy", handler.GetProxy)
	router.GET("/proxy/rotate", handler.RotateProxy)
	router.GET("/proxy/list", handler.ListProxies)
	router.POST("/proxy/add", handler.AddProxy)
	router.DELETE("/proxy/:id", handler.RemoveProxy)
	router.GET("/proxy/stats", handler.GetStats)

	server := &http.Server{
		Addr:    ":" + cfg.Port,
		Handler: router,
	}

	go func() {
		log.Printf("Starting proxy service on port %s", cfg.Port)
		if err := server.ListenAndServe(); err != nil && err != http.ErrServerClosed {
			log.Fatalf("Failed to start server: %v", err)
		}
	}()

	quit := make(chan os.Signal, 1)
	signal.Notify(quit, syscall.SIGINT, syscall.SIGTERM)
	<-quit

	log.Println("Shutting down proxy service...")
}
