package storage

import (
	"sync"
	"time"
)

type Storage interface {
	SaveProxy(proxy *Proxy) error
	GetProxy(id string) (*Proxy, error)
	GetAllProxies() ([]*Proxy, error)
	DeleteProxy(id string) error
}

type MemoryStorage struct {
	proxies map[string]*Proxy
	mu      sync.RWMutex
}

type Proxy struct {
	ID       string    `json:"id"`
	Host     string    `json:"host"`
	Port     int       `json:"port"`
	Username string    `json:"username"`
	Password string    `json:"password"`
	Protocol string    `json:"protocol"`
	LastUsed time.Time `json:"last_used"`
	Failures int       `json:"failures"`
	Active   bool      `json:"active"`
}

func NewMemoryStorage() *MemoryStorage {
	return &MemoryStorage{
		proxies: make(map[string]*Proxy),
	}
}

func (m *MemoryStorage) SaveProxy(proxy *Proxy) error {
	m.mu.Lock()
	defer m.mu.Unlock()
	m.proxies[proxy.ID] = proxy
	return nil
}

func (m *MemoryStorage) GetProxy(id string) (*Proxy, error) {
	m.mu.RLock()
	defer m.mu.RUnlock()
	
	proxy, exists := m.proxies[id]
	if !exists {
		return nil, nil
	}
	return proxy, nil
}

func (m *MemoryStorage) GetAllProxies() ([]*Proxy, error) {
	m.mu.RLock()
	defer m.mu.RUnlock()
	
	proxies := make([]*Proxy, 0, len(m.proxies))
	for _, proxy := range m.proxies {
		proxies = append(proxies, proxy)
	}
	return proxies, nil
}

func (m *MemoryStorage) DeleteProxy(id string) error {
	m.mu.Lock()
	defer m.mu.Unlock()
	delete(m.proxies, id)
	return nil
}
